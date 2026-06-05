<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Détecte et supprime les doublons OCR dans la base de transactions.
 *
 * Algorithme double-passe :
 *  Pass 1 – Exact  : même date + même montant + même type + label canonique identique
 *  Pass 2 – Fuzzy  : date ±2j  + même montant + même type + label canonique similaire (Levenshtein ≤ 22%)
 *
 * Règle de conservation : on garde la transaction avec le label le plus court
 * (moins de bruit OCR) ou, à longueur égale, le plus ancien id.
 */
class DeduplicationService
{
    /** Fenêtre de date pour la passe fuzzy (jours) */
    private const FUZZY_DAYS = 2;

    /** Longueur minimale du canonique pour autoriser la passe fuzzy */
    private const FUZZY_MIN_CANON_LEN = 12;

    /**
     * Mots-clés qui identifient la "catégorie" d'une transaction.
     * Deux transactions de catégories différentes ne peuvent pas être des doublons.
     */
    private function transactionCategory(string $canonical): string
    {
        if (str_contains($canonical, 'BORDEREAU'))       return 'bordereau';
        if (str_contains($canonical, 'CHEQUE'))          return 'cheque';
        if (preg_match('/\bPRLV\b|\bPRELEVEMENT\b/', $canonical)) return 'prelevement';
        if (preg_match('/\bVIR\b|\bVER\b/', $canonical)) return 'virement';
        if (preg_match('/\bRETRAIT\b|\bDAB\b/', $canonical)) return 'retrait';
        if (preg_match('/\bFACTURE\b|\bCARTE\b/', $canonical)) return 'carte';
        return 'autre';
    }

    /** Ratio Levenshtein max pour considérer deux labels identiques */
    private const LEVENSHTEIN_RATIO = 0.22;

    public function deduplicateAccount(BankAccount $account, bool $dryRun = false): array
    {
        $stats = ['examined' => 0, 'duplicates_found' => 0, 'deleted' => 0, 'details' => []];

        // Charger toutes les transactions du compte, triées par date puis id
        $transactions = Transaction::query()
            ->where('bank_account_id', $account->getKey())
            ->orderBy('date')
            ->orderBy('id')
            ->get(['id', 'date', 'amount', 'type', 'label', 'normalized_label']);

        $stats['examined'] = $transactions->count();
        $toDelete = [];   // id → raison

        $processed = [];  // index dans $transactions déjà marqués comme doublons

        foreach ($transactions as $i => $txA) {
            if (isset($processed[$i])) continue;

            $dateA   = $txA->date;
            $amountA = (float) $txA->amount;
            $typeA   = $txA->type;
            $canonA  = $this->canonicalize((string) $txA->normalized_label);

            foreach ($transactions as $j => $txB) {
                if ($j <= $i) continue;
                if (isset($processed[$j])) continue;
                if ($txB->type !== $typeA) continue;
                if (abs((float) $txB->amount - $amountA) > 0.005) continue;

                // Fenêtre de date
                $daysDiff = abs((int) \Carbon\Carbon::parse($dateA)->diffInDays(\Carbon\Carbon::parse($txB->date)));
                if ($daysDiff > self::FUZZY_DAYS) {
                    // Transactions triées par date : si on dépasse la fenêtre, plus rien à comparer
                    if ($daysDiff > self::FUZZY_DAYS + 1) break;
                    continue;
                }

                $canonB = $this->canonicalize((string) $txB->normalized_label);
                if ($canonA === '' || $canonB === '') continue;

                // Les deux transactions doivent appartenir à la même catégorie
                $catA = $this->transactionCategory($canonA);
                $catB = $this->transactionCategory($canonB);
                if ($catA !== $catB) continue;

                // Pour "retrait" : jamais de dédup cross-date (même 120€ à 15H14 et 16H43
                // au même DAB = deux retraits légitimes).
                // Pour "carte" : uniquement si le canonique est très spécifique (≥ 22 chars).
                if ($daysDiff > 0 && $catA === 'retrait') continue;
                if ($daysDiff > 0 && $catA === 'carte') {
                    if (mb_strlen($canonA) < self::FUZZY_MIN_CANON_LEN + 10) continue;
                }

                $isDuplicate = false;
                $reason = '';

                // Pass 1 : labels canoniques identiques
                if ($canonA === $canonB) {
                    $isDuplicate = true;
                    $reason = "exact_canon (date_diff={$daysDiff}j)";
                }

                // Pass 2 : Levenshtein fuzzy — seulement si le canonique est assez long
                // pour être discriminant, et avec un seuil plus strict
                if (!$isDuplicate && mb_strlen($canonA) >= self::FUZZY_MIN_CANON_LEN && mb_strlen($canonB) >= self::FUZZY_MIN_CANON_LEN) {
                    $maxLen = max(mb_strlen($canonA), mb_strlen($canonB), 1);
                    $dist   = levenshtein(mb_substr($canonA, 0, 255), mb_substr($canonB, 0, 255));
                    $ratio  = $dist / $maxLen;
                    if ($ratio <= self::LEVENSHTEIN_RATIO) {
                        $isDuplicate = true;
                        $reason = sprintf('fuzzy_levenshtein ratio=%.2f (date_diff=%dj)', $ratio, $daysDiff);
                    }
                }

                if (!$isDuplicate) continue;

                // Choisir lequel garder : label le moins bruité (plus court) ou id le plus petit
                $lenA = mb_strlen((string) $txA->normalized_label);
                $lenB = mb_strlen((string) $txB->normalized_label);
                $keepId   = ($lenA <= $lenB) ? $txA->id : $txB->id;
                $deleteId = ($lenA <= $lenB) ? $txB->id : $txA->id;
                $deleteIdx = ($lenA <= $lenB) ? $j : $i;

                $toDelete[$deleteId] = $reason;
                $processed[$deleteIdx] = true;

                $stats['details'][] = [
                    'kept'    => $keepId,
                    'deleted' => $deleteId,
                    'reason'  => $reason,
                    'date'    => $dateA,
                    'amount'  => $amountA,
                    'label_a' => mb_substr((string) $txA->label, 0, 80),
                    'label_b' => mb_substr((string) $txB->label, 0, 80),
                ];

                $stats['duplicates_found']++;
            }
        }

        if (!$dryRun && !empty($toDelete)) {
            $deleted = Transaction::whereIn('id', array_keys($toDelete))->delete();
            $stats['deleted'] = $deleted;
            Log::info("DeduplicationService: supprimé {$deleted} doublon(s) OCR sur compte #{$account->getKey()}");
        }

        return $stats;
    }

    public function deduplicateCase(int $caseId, bool $dryRun = false): array
    {
        $results = [];
        $accounts = BankAccount::where('case_id', $caseId)->get();
        foreach ($accounts as $account) {
            $results[$account->id] = $this->deduplicateAccount($account, $dryRun);
        }
        return $results;
    }

    /**
     * Normalise un label pour la comparaison : supprime numéros, bruit OCR,
     * pied de page BNP, numéros de bordereau, etc.
     */
    private function canonicalize(string $label): string
    {
        $s = mb_strtoupper($label);
        // Supprimer pied de page BNP (OCR ou non)
        $s = preg_replace('/\b[BEFP]NP\s+PAR[\w\'\-\.]*BAS\b.*/u', '', $s) ?? $s;
        // Tronquer après BORDEREAU (le numéro varie selon lecture OCR)
        $s = preg_replace('/\bR?BORDEREAU\b.*/u', 'BORDEREAU', $s) ?? $s;
        // Supprimer champs SEPA
        $s = preg_replace('/\b(?:REF|EMETTEUR|MDT|IBAN|BIC|RIB|LIB|NOPT|NB CHQ)\b.*/u', '', $s) ?? $s;
        // Supprimer dates DD.MM ou DDMMYY
        $s = preg_replace('/\b\d{1,2}[.\/]\d{2}(?:[.\/]\d{2,4})?\b/u', '', $s) ?? $s;
        // Supprimer tous les chiffres isolés et longues séquences alphanumériques (IBANs, refs)
        $s = preg_replace('/\b[A-Z0-9]{10,}\b/u', '', $s) ?? $s;
        $s = preg_replace('/\b\d+\b/u', '', $s) ?? $s;
        // Supprimer ponctuation isolée
        $s = preg_replace('/(?<!\w)[.\-\/\\\\](?!\w)/u', ' ', $s) ?? $s;
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
        return trim($s);
    }
}
