<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$accounts = \App\Models\BankAccount::with('statements')->get();
foreach ($accounts as $account) {
    echo "=== Compte {$account->id} ({$account->bank_name} · {$account->account_holder}) ===\n";
    foreach ($account->statements->take(1) as $stmt) {
        $text = $stmt->extracted_text ?? '';
        if (!$text) { echo "(pas de texte OCR)\n"; continue; }
        $lines = array_slice(explode("\n", $text), 0, 40);
        foreach ($lines as $i => $line) {
            $line = trim($line);
            if ($line === '') continue;
            echo sprintf("[%02d] %s\n", $i, $line);
        }
    }
    echo "\n";
}
