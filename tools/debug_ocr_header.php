<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$statementId = (int) ($argv[1] ?? 11);
$s = \App\Models\Statement::find($statementId);
if (!$s) { echo "Statement not found\n"; exit(1); }

$text = $s->extracted_text ?? '';
$lines = preg_split('/\R/u', $text) ?: [];

echo "=== FIRST 60 LINES (headers) ===\n";
for ($i = 0; $i < min(60, count($lines)); $i++) {
    echo sprintf("[%3d] %s\n", $i, $lines[$i]);
}

echo "\n=== LOOKING FOR YEAR PATTERNS ===\n";
// Look for 4-digit years
preg_match_all('/\b20\d{2}\b/', $text, $matches, PREG_OFFSET_CAPTURE);
foreach (array_slice($matches[0], 0, 20) as $m) {
    $pos = (int) $m[1];
    $context = mb_substr($text, max(0, $pos - 30), 80);
    echo sprintf("Year '%s' at offset %d: ...%s...\n", $m[0], $pos, str_replace("\n", '↵', $context));
}

echo "\n=== LOOKING FOR 'DU' + DATE PATTERN ===\n";
preg_match_all('/du\s+\d{1,2}[^\n]{0,60}/i', $text, $matches2);
foreach (array_slice($matches2[0], 0, 10) as $m) {
    echo "  " . str_replace("\n", '↵', $m) . "\n";
}
