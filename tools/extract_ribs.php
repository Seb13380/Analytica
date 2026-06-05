<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$accounts = \App\Models\BankAccount::with('statements')->get();
foreach ($accounts as $account) {
    if (!empty($account->rib)) {
        echo "Compte {$account->id} ({$account->account_holder}): RIB déjà présent: {$account->rib}\n";
        continue;
    }
    $found = null;
    foreach ($account->statements as $stmt) {
        $text = $stmt->extracted_text ?? '';
        if (!$text) continue;
        $upper = mb_strtoupper($text);
        // RIB : banque(5) + guichet(5) + compte(11) + clé(2) = 23 chiffres
        if (preg_match('/\bRIB\s*[:\-\|]?\s*([0-9][0-9 ]{18,28}[0-9])\b/u', $upper, $m)) {
            $rib = preg_replace('/\s+/', ' ', trim($m[1]));
            $digits = preg_replace('/\D/', '', $rib);
            if (strlen($digits) === 23) { $found = $rib; break; }
        }
        // IBAN FR
        if (preg_match('/\bIBAN\s*[:\-]?\s*(FR\s?\d{2}(?:\s?[A-Z0-9]{4}){4,6}(?:\s?[A-Z0-9]{0,3})?)/u', $upper, $m)) {
            $found = preg_replace('/\s+/', ' ', trim($m[1])); break;
        }
        // IBAN sans label (dans les 50 premières lignes)
        $lines = array_slice(explode("\n", $upper), 0, 50);
        foreach ($lines as $line) {
            if (preg_match('/\b(FR\d{2}(?:\s?[A-Z0-9]){18,27})\b/u', $line, $m)) {
                $c = preg_replace('/\s+/', ' ', trim($m[1]));
                if (strlen(preg_replace('/\s/', '', $c)) >= 14) { $found = $c; break 2; }
            }
        }
    }
    if ($found) {
        $account->forceFill(['rib' => $found])->save();
        echo "Compte {$account->id} ({$account->bank_name} · {$account->account_holder}): RIB extrait → {$found}\n";
    } else {
        echo "Compte {$account->id} ({$account->bank_name} · {$account->account_holder}): aucun RIB trouvé\n";
    }
}
echo "\nTerminé.\n";
