<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$accounts = \App\Models\BankAccount::with('statements')->get();

foreach ($accounts as $account) {
    $textParts = [];
    foreach ($account->statements as $stmt) {
        $m = \App\Models\Statement::find($stmt->id);
        $t = $m->extracted_text ?? '';
        if ($t) $textParts[] = $t;
    }
    $text = implode("\n", $textParts);
    $upper = mb_strtoupper($text);
    $lines = array_slice(explode("\n", $upper), 0, 100);

    $type   = null;
    $holder = null;
    $rib    = null;

    // ── Détecter type ──────────────────────────────────────────────────────
    // Indice sur account_holder déjà en base (ex: "Livret DEV / Épargne")
    $existingHolder = mb_strtolower((string)($account->account_holder ?? ''));
    if (str_contains($existingHolder, 'livret') || str_contains($existingHolder, 'épargne') || str_contains($existingHolder, 'epargne')) {
        $type = 'savings';
    }

    if ($type === null && $text !== '') {
        // "M OU MME ..." ou "M ET MME ..." = compte commun (OCR peut remplacer I par :)
        if (preg_match('/\bM\.?\s+(?:OU|ET)\s+MME\.?\b/u', $upper)) {
            $type = 'joint';
        }
        // "LIVRET" ou "EPARGNE" n'importe où = épargne
        if ($type === null && preg_match('/\bLIVRET\b|\bEPARGNE\b|\bLDD\b|\bLDDS\b|\bLEP\b/u', $upper)) {
            $type = 'savings';
        }
    }
    if ($type === null) $type = 'personal';

    // ── Extraire titulaire ─────────────────────────────────────────────────
    // BNP Succession
    if (preg_match('/Succession\s+de\s*:\s*(?:M(?:ONS(?:IEUR)?|ME|\.)?\.?|MME\.?|MADAME|MR\.?)\s+([A-ZÀÂÄÉÈÊËÏÎÔÙÛÜÇ][A-ZÀÂÄÉÈÊËÏÎÔÙÛÜÇ\- ]+?)(?:\s+n[eé]\b|$)/iu', $text, $m)) {
        $holder = preg_replace('/\s+/', ' ', trim($m[1]));
        $holder = mb_convert_case($holder, MB_CASE_TITLE, 'UTF-8');
    }
    // En-tête standard: "M CHRISTIAN GIORDANO" ou "M OU MME CHRISTIAN GIORDANO"
    if (!$holder) {
        $cv = 'M(?:ONS(?:IEUR)?|ME|\.)?\.?|MME\.?|MADAME|MR\.?';
        foreach ($lines as $line) {
            $line = trim($line);
            if (strlen($line) < 5 || strlen($line) > 80) continue;
            if (preg_match('/^(?:' . $cv . ')\s+(?:OU|ET)\s+(?:' . $cv . ')\s+([A-ZÀÂÄÉÈÊËÏÎÔÙÛÜÇ][A-ZÀÂÄÉÈÊËÏÎÔÙÛÜÇ\- ]{2,50})$/u', $line, $m)) {
                $holder = mb_convert_case(trim($m[1]), MB_CASE_TITLE, 'UTF-8'); break;
            }
            if (preg_match('/^(?:' . $cv . ')\s+([A-ZÀÂÄÉÈÊËÏÎÔÙÛÜÇ][A-Z]{1,20})\s+([A-ZÀÂÄÉÈÊËÏÎÔÙÛÜÇ][A-Z\-]{1,30})$/u', $line, $m)) {
                $holder = mb_convert_case($m[1], MB_CASE_TITLE, 'UTF-8') . ' ' . mb_convert_case($m[2], MB_CASE_TITLE, 'UTF-8'); break;
            }
        }
    }

    // ── Extraire RIB / IBAN (parcours texte complet) ──────────────────────
    if (preg_match('/\bRIB\s*[:\|]?\s*([0-9][0-9 ]{18,28}[0-9])/u', $upper, $m)) {
        $r = preg_replace('/\s+/', ' ', trim($m[1]));
        if (strlen(preg_replace('/\D/', '', $r)) === 23) $rib = $r;
    }
    if (!$rib && preg_match('/\bIBAN\s*[:\-]?\s*(FR\s?\d{2}(?:\s?[A-Z0-9]{4}){4,6}(?:\s?[A-Z0-9]{0,3})?)/u', $upper, $m)) {
        $rib = preg_replace('/\s+/', ' ', trim($m[1]));
    }

    // ── Sauvegarder ────────────────────────────────────────────────────────
    $updates = ['account_type' => $type];
    if ($holder) $updates['account_holder'] = $holder;
    if ($rib && empty($account->rib)) $updates['rib'] = $rib;

    $account->forceFill($updates)->save();

    echo "Compte {$account->id} ({$account->bank_name}): type={$type} | holder=" . ($holder ?? '—') . " | rib=" . ($rib ?? ($account->rib ?? '—')) . "\n";
}
echo "\nTerminé.\n";
