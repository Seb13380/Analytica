<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$genericHolders = ['compte personnel', 'livret dev / épargne', 'livret dev / epargne', 'm. / mme', 'm. ou mme', ''];
$civilites = 'M(?:ONS(?:IEUR)?|ME|\.)?|MME\.?|MADAME|MR\.?';

function extractHolder(string $text, string $civilites): ?string {
    // Priorité 1 : BNP Succession "Succession de : M. CHRISTIAN GIORDANO né GIORDANO"
    if (preg_match('/Succession\s+de\s*:\s*(?:M(?:ONS(?:IEUR)?|ME|\.)?\s*\.?|MME\.?|MADAME|MR\.?)\s+([A-ZÀÂÄÉÈÊËÏÎÔÙÛÜÇ][A-ZÀÂÄÉÈÊËÏÎÔÙÛÜÇ\- ]+?)(?:\s+n[eé]é?\b|$)/iu', $text, $m)) {
        return mb_convert_case(preg_replace('/\s+/', ' ', trim($m[1])), MB_CASE_TITLE, 'UTF-8');
    }
    // Priorité 2 : en-tête courant
    $upper = mb_strtoupper($text);
    $lines = array_slice(explode("\n", $upper), 0, 80);
    $cv = 'M(?:ONS(?:IEUR)?|ME|\.)?\.?|MME\.?|MADAME|MR\.?';
    foreach ($lines as $line) {
        $line = trim($line);
        if (strlen($line) < 5 || strlen($line) > 80) continue;
        if (preg_match('/^(?:' . $cv . ')\s+(?:OU|ET)\s+(?:' . $cv . ')\s+([A-ZÀÂÄÉÈÊËÏÎÔÙÛÜÇ][A-ZÀÂÄÉÈÊËÏÎÔÙÛÜÇ\- ]{2,50})$/u', $line, $m))
            return mb_convert_case(trim($m[1]), MB_CASE_TITLE, 'UTF-8');
        if (preg_match('/^(?:' . $cv . ')\s+([A-ZÀÂÄÉÈÊËÏÎÔÙÛÜÇ][A-Z]{1,20})\s+([A-ZÀÂÄÉÈÊËÏÎÔÙÛÜÇ][A-Z\-]{1,30})$/u', $line, $m))
            return mb_convert_case($m[1], MB_CASE_TITLE, 'UTF-8') . ' ' . mb_convert_case($m[2], MB_CASE_TITLE, 'UTF-8');
    }
    return null;
}

$accounts = \App\Models\BankAccount::with('statements')->get();
foreach ($accounts as $account) {
    $currentHolder = mb_strtolower(trim((string)($account->account_holder ?? '')));
    if (!in_array($currentHolder, $genericHolders)) {
        echo "Compte {$account->id} ({$account->bank_name}): titulaire déjà renseigné → {$account->account_holder}\n";
        continue;
    }

    $found = null;
    foreach ($account->statements as $stmt) {
        // extracted_text est chiffré — utiliser le modèle Statement (pas DB brute)
        $stmtModel = \App\Models\Statement::find($stmt->id);
        $text = $stmtModel->extracted_text ?? '';
        if (!$text) continue;
        $found = extractHolder($text, $civilites);
        if ($found) break;
    }

    if ($found) {
        $account->forceFill(['account_holder' => $found])->save();
        echo "Compte {$account->id} ({$account->bank_name}): titulaire extrait → {$found}\n";
    } else {
        echo "Compte {$account->id} ({$account->bank_name} · {$account->account_holder}): aucun titulaire trouvé dans les relevés\n";
    }
}
echo "\nTerminé.\n";
