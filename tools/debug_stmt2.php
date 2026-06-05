<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check statement 2's RIB in its OCR text
$stmt = \App\Models\Statement::find(2);
$text = $stmt->extracted_text ?? '';
$upper = mb_strtoupper($text);

echo "=== Comptes IBAN dans relevé 2 ===\n";
preg_match_all('/FR\s?\d{2}(?:\s?[A-Z0-9]{4}){4,7}/u', $upper, $m);
$ibans = array_unique($m[0]);
foreach ($ibans as $iban) {
    echo trim($iban) . "\n";
}

echo "\n=== Recherche RIB ===\n";
preg_match_all('/RIB.{0,10}([0-9][0-9 ]{15,28}[0-9])/u', $upper, $m);
foreach ($m[0] as $match) echo $match . "\n";

echo "\n=== 30 premières lignes ===\n";
$lines = explode("\n", $text);
foreach (array_slice($lines, 0, 30) as $i => $l) {
    echo "L{$i}: " . trim($l) . "\n";
}
