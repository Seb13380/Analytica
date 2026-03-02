<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check a small sample from within the text where transactions would be
$stmt = App\Models\Statement::find(10);
$text = (string)($stmt->extracted_text ?? '');

// Find first "RELEVE DE COMPTE" position
$upper = mb_strtoupper($text);
$pos = mb_strpos($upper, 'RELEVE DE COMPTE');
echo "=== First 'RELEVE DE COMPTE' at char pos: $pos ===\n";
if ($pos !== false) {
    echo "Context around it (200 chars before/after):\n";
    echo mb_substr($text, max(0, $pos - 50), 400)."\n\n";
}

// Find first "M OU MME" position  
$pos2 = mb_strpos($upper, 'M OU MME');
echo "=== First 'M OU MME' at char pos: $pos2 ===\n";
if ($pos2 !== false) {
    echo "Context:\n";
    echo mb_substr($text, max(0, $pos2 - 100), 300)."\n\n";
}

// Find LIVRET
$pos3 = mb_strpos($upper, 'RELEVE LIVRET');
echo "=== First 'RELEVE LIVRET' at char pos: ".($pos3 === false ? 'NOT FOUND' : $pos3)." ===\n";

// Check Normalization::cleanLabel on the RELEVE DE COMPTE line
$app2 = require __DIR__.'/../bootstrap/app.php';
$service = app(\App\Services\StatementImportService::class);

// Find the actual line
$lines = preg_split('/\R/', $text);
foreach ($lines as $i => $line) {
    if (stripos($line, 'RELEVE DE COMPTE') !== false) {
        $cleaned = \App\Services\Normalization::cleanLabel($line);
        $upCleaned = mb_strtoupper($cleaned);
        echo "Line $i raw: '$line'\n";
        echo "Line $i cleaned: '$cleaned'\n";
        $matches = preg_match('/BNP\s*PARIBAS\s+RELEVE\s+DE\s+COMPTE|RELEVE\s+DE\s+COMPTE\s+CH[EÈ]QUES|RELEVÉ\s+DE\s+COMPTE\s+CH[EÈ]QUES|BNP\s*PARIBAS\s+RELEVÉ\s+DE\s+COMPTE/u', $upCleaned);
        echo "Section regex match: ".($matches ? 'YES' : 'NO')."\n\n";
        if ($i > 50) break; // first few occurrences
    }
}
