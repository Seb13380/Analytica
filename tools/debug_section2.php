<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Test the regex fix
$tests = [
    'M OU MME GIORDANO',
    'M OÙ MME GIORDANO',
    'M. OU MME GIORDANO',
    'M GIORDANO CHRISTIAN', // should NOT match
    'MLLE GIORDANO', // should NOT match
];

$pattern = '/\bM\s+O[U\x{00D9}]\s+MM[E\x{00C8}\x{00C9}]?\b|\bM\.\s*O[U\x{00D9}]\s+MM[E\x{00C8}\x{00C9}]?\b|\bM\s+ET\s+MM[E\x{00C8}\x{00C9}]?\b/u';

echo "=== Testing 'M OU/OÙ MME' pattern ===\n";
foreach ($tests as $t) {
    $upper = mb_strtoupper($t);
    $matches = (bool) preg_match($pattern, $upper);
    echo ($matches ? '✓ MATCH   ' : '✗ NO MATCH') . " '$t'\n";
}

echo "\n=== Testing in context from stmt10 OCR ===\n";
$stmt = App\Models\Statement::find(10);
$text = (string)($stmt->extracted_text ?? '');
$lines = preg_split('/\R/', $text);

$detectedSection = 'joint'; // default
$pendingDetect = 0;
$sectionChanges = [];

foreach ($lines as $i => $rawLine) {
    $line = \App\Services\Normalization::cleanLabel((string)$rawLine);
    if ($line === '') continue;
    $upper = mb_strtoupper($line);
    
    if (preg_match('/RELEVE\s+LIVRET|RELEVÉ\s+LIVRET/u', $upper)) {
        $detectedSection = 'savings';
        $pendingDetect = 0;
        $sectionChanges[] = "Line $i: SECTION→savings (LIVRET)";
    } elseif (preg_match('/BNP\s*PARIBAS\s+RELEVE\s+DE\s+COMPTE|RELEVE\s+DE\s+COMPTE\s+CH[EÈ]QUES|RELEVÉ\s+DE\s+COMPTE\s+CH[EÈ]QUES|BNP\s*PARIBAS\s+RELEVÉ\s+DE\s+COMPTE/u', $upper)) {
        $detectedSection = 'personal';
        $pendingDetect = 8;
        $sectionChanges[] = "Line $i: Section reset→personal (RELEVE DE COMPTE header found)";
    } elseif ($pendingDetect > 0) {
        $pendingDetect--;
        if (preg_match($pattern, $upper)) {
            $detectedSection = 'joint';
            $pendingDetect = 0;
            $sectionChanges[] = "Line $i: SECTION→joint (M OU/OÙ MME found: '$line')";
        }
    }
    
    if ($i > 500) break; // only check first 500 lines
}

foreach ($sectionChanges as $change) {
    echo "$change\n";
}
echo "Final section after first 500 lines: $detectedSection\n";
