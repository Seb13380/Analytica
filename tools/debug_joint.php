<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$stmt = \App\Models\Statement::where('bank_account_id', 1)->first();
$text = $stmt->extracted_text ?? '';
$upper = mb_strtoupper($text);
$lines = explode("\n", $upper);

echo "Cherche M OU MME / M ET MME / compte commun...\n\n";
foreach ($lines as $i => $line) {
    if (preg_match('/M\.?\s+(?:OU|ET)\s+MME/u', $line) || stripos($line, 'COMMUN') !== false) {
        echo "L{$i}: " . trim($line) . "\n";
    }
}

echo "\nCherche GARDANNE (zone titulaire)...\n";
foreach ($lines as $i => $line) {
    if (stripos($line, 'GARDANNE') !== false && $i > 100) {
        // Afficher les 5 lignes autour
        for ($j = max(0, $i-3); $j <= min(count($lines)-1, $i+3); $j++) {
            echo "L{$j}: " . trim($lines[$j]) . "\n";
        }
        echo "---\n";
        break;
    }
}
