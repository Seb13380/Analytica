<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$stmt = \App\Models\Statement::where('bank_account_id', 1)->first();
$text = $stmt->extracted_text ?? '';
echo "Longueur texte: " . strlen($text) . "\n\n";
foreach (explode("\n", $text) as $i => $line) {
    if (stripos($line, 'succession') !== false) {
        echo "L{$i}: [" . trim($line) . "]\n";
    }
}
