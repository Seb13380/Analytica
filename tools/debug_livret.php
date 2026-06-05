<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Account 3 = Livret DEV
$stmt = \App\Models\Statement::where('bank_account_id', 3)->first();
if (!$stmt) { echo "Pas de relevé pour compte 3\n"; exit; }
$text = $stmt->extracted_text ?? '';
$upper = mb_strtoupper($text);

// Chercher RIB et IBAN
echo "=== Cherche RIB ===\n";
preg_match_all('/RIB.{0,5}[:\|]?\s*([0-9][0-9 ]{10,30}[0-9])/u', $upper, $m);
foreach ($m[0] as $match) echo $match . "\n";

echo "\n=== Cherche IBAN ===\n";
preg_match_all('/IBAN.{0,5}[:\-]?\s*(FR\s?\d{2}(?:\s?[A-Z0-9]{4}){4,7})/u', $upper, $m);
foreach ($m[0] as $match) echo $match . "\n";

echo "\n=== Cherche LIVRET ===\n";
$lines = explode("\n", $upper);
foreach ($lines as $i => $line) {
    if (str_contains($line, 'LIVRET') || str_contains($line, 'LDD') || str_contains($line, '75123')) {
        echo "L{$i}: " . trim($line) . "\n";
    }
}
