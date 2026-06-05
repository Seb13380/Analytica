<?php
/**
 * Répare l'état après la mauvaise fusion.
 * - Statement 2 (Livret DEV) repart vers son compte Livret
 * - Extrait le bon RIB depuis chaque relevé
 * - Recorrige account_type
 */
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// ── 1. Identifier le compte Livret (account_type=savings ou holder contient Livret)
$livretAccount = \App\Models\BankAccount::where('account_type', 'savings')
    ->orWhere(function($q){ $q->whereRaw("lower(account_holder) like '%livret%'"); })
    ->orderBy('id')->first();

if (!$livretAccount) {
    echo "ERREUR: pas de compte Livret trouvé\n";
    exit(1);
}
echo "Compte Livret = #{$livretAccount->id} ({$livretAccount->account_holder})\n";

// ── 2. Pour chaque statement, extraire le RIB depuis son OCR et l'assigner au bon compte
$statements = \App\Models\Statement::all();
foreach ($statements as $stmt) {
    $text  = $stmt->extracted_text ?? '';
    $upper = mb_strtoupper($text);

    // Extraire RIB en base 23 chiffres (format BNP: BBBBB GGGGG NNNNNNNNNNN CC)
    $rib = null;
    if (preg_match('/\bRIB\s*[_:\|]?\s*([0-9]{5}\s*[0-9]{5}\s*[0-9]{11}\s*[0-9]{2})\b/u', $upper, $m)) {
        $rib = preg_replace('/\s+/', ' ', trim($m[1]));
    }
    if (!$rib && preg_match('/\bIBAN\s*[:\-]?\s*(FR\s?\d{2}(?:\s?[A-Z0-9]{4}){5,6}(?:\s?[A-Z0-9]{0,3})?)/u', $upper, $m)) {
        $rib = preg_replace('/\s+/', ' ', trim($m[1]));
    }

    // Détecter le type depuis le texte de CE relevé
    $isJoint   = (bool) preg_match('/\bM\.?\s+(?:OU|ET)\s+MME\.?\b/u', $upper);
    $isSavings = (bool) preg_match('/\bLIVRET\b|\bLDD\b|\bLDDS\b/u', $upper);

    echo "\nStatement #{$stmt->id} (compte_id={$stmt->bank_account_id}):\n";
    echo "  RIB extrait: " . ($rib ?? '(non trouvé)') . "\n";
    echo "  Type: " . ($isSavings ? 'savings' : ($isJoint ? 'joint' : 'personal')) . "\n";

    // Choisir le bon compte pour ce relevé
    if ($isSavings) {
        $targetAccountId = $livretAccount->id;
    } else {
        // Compte non-Livret → trouver le compte avec RIB correspondant ou le compte joint
        $targetAccount = \App\Models\BankAccount::where('account_type', 'joint')
            ->orWhere('account_type', 'personal')
            ->orderBy('id')->first();
        $targetAccountId = $targetAccount ? $targetAccount->id : $stmt->bank_account_id;
    }

    if ($stmt->bank_account_id !== $targetAccountId) {
        echo "  → Déplacement statement #{$stmt->id} vers compte #{$targetAccountId}\n";
        DB::table('statements')->where('id', $stmt->id)->update(['bank_account_id' => $targetAccountId]);
        // Déplacer aussi les transactions — avec gestion doublons
        $txIds = DB::table('transactions')->where('bank_account_id', $stmt->bank_account_id)->pluck('id');
        $moved = 0; $deduped = 0;
        foreach ($txIds as $txId) {
            try {
                DB::table('transactions')->where('id', $txId)->update(['bank_account_id' => $targetAccountId]);
                $moved++;
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                DB::table('transactions')->where('id', $txId)->delete();
                $deduped++;
            }
        }
        echo "  → {$moved} transactions déplacées, {$deduped} doublons supprimés\n";
    } else {
        echo "  → Déjà sur le bon compte\n";
    }

    // Mettre à jour le RIB et le type du compte cible
    if ($rib) {
        DB::table('bank_accounts')->where('id', $targetAccountId)->update(['rib' => $rib]);
        echo "  → RIB sauvegardé sur compte #{$targetAccountId}\n";
    }
    $type = $isSavings ? 'savings' : ($isJoint ? 'joint' : 'personal');
    DB::table('bank_accounts')->where('id', $targetAccountId)->update(['account_type' => $type]);
}

// ── 3. Supprimer les comptes vides (sans statements ni transactions) sauf compte 1
$toDelete = \App\Models\BankAccount::all()->filter(function($acc) {
    $stmts = DB::table('statements')->where('bank_account_id', $acc->id)->count();
    $txs   = DB::table('transactions')->where('bank_account_id', $acc->id)->count();
    return $stmts === 0 && $txs === 0;
});
foreach ($toDelete as $acc) {
    echo "\nSuppression compte vide #{$acc->id} ({$acc->account_holder})\n";
    $acc->delete();
}

// ── 4. État final
echo "\n=== ÉTAT FINAL ===\n";
foreach (\App\Models\BankAccount::orderBy('id')->get() as $acc) {
    $stmts = DB::table('statements')->where('bank_account_id', $acc->id)->count();
    $txs   = DB::table('transactions')->where('bank_account_id', $acc->id)->count();
    echo "#{$acc->id} {$acc->bank_name} | {$acc->account_type} | {$acc->account_holder} | RIB: {$acc->rib} | {$stmts} relevés | {$txs} tx\n";
}
echo "\nTerminé.\n";
