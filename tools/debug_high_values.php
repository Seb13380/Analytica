<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$statementId = (int) ($argv[1] ?? 11);
$threshold  = (float) ($argv[2] ?? 20000);

$s = \App\Models\Statement::find($statementId);
if (!$s) {
    echo "Statement not found\n";
    exit(1);
}

$text = $s->extracted_text ?? '';
$lines = preg_split('/\R/u', $text) ?: [];

$amountRegex = '/-?(?:\d{1,3}(?:[\s\x{00A0}.]\d{3})+|\d+)(?:[\.,]\d{2})|-?\d{1,3}(?:[\s\x{00A0}.]\d{3})+(?:\s?(?:€|EUR))?/u';

foreach ($lines as $i => $line) {
    $line = trim($line);
    if ($line === '') continue;

    preg_match_all($amountRegex, $line, $matches);
    foreach ($matches[0] as $raw) {
        $normalized = str_replace([' ', "\xc2\xa0", '.'], '', rtrim($raw));
        $normalized = str_replace(',', '.', $normalized);
        $value = abs((float) $normalized);
        if ($value >= $threshold) {
            echo sprintf("[%4d] value=%s | raw='%s' | line='%s'\n",
                $i, number_format($value, 2, ',', ' '), $raw, mb_substr($line, 0, 100));
        }
    }
}
