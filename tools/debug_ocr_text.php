<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$statementId = (int) ($argv[1] ?? 10);
$s = \App\Models\Statement::find($statementId);
if (!$s) {
    echo "Statement not found\n";
    exit(1);
}

$text = $s->extracted_text ?? '';
$lines = preg_split('/\R/u', $text) ?: [];

$searchTerms = ['EDF', 'ECH', 'EMETTEUR', 'BNP PARIBAS SA', 'CAPITAL', '7029', '7 029'];

foreach ($lines as $i => $line) {
    foreach ($searchTerms as $term) {
        if (mb_stripos($line, $term) !== false) {
            echo sprintf("[%4d] %s\n", $i, $line);
            break;
        }
    }
}
