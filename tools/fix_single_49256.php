<?php

require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Transaction;

$tx = Transaction::find(49256);
if (!$tx) {
    echo "Transaction #49256 not found\n";
    exit(1);
}

$oldDate = (string) $tx->date;
$tx->date = '2025-09-02';
$tx->save();

echo "Fixed #49256: {$oldDate} -> 2025-09-02\n";
