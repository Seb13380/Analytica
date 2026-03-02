<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$id = (int)($argv[1] ?? 0);
if (!$id) {
    echo "Usage: php tools/delete_tx.php <transaction_id>\n";
    exit(1);
}

$row = DB::table('transactions')->where('id', $id)->first();
if (!$row) {
    echo "Transaction $id not found.\n";
    exit(1);
}

echo "Deleting:\n";
echo "  id={$row->id} | {$row->date} | {$row->type} | {$row->amount}€\n";
echo "  " . substr($row->label, 0, 120) . "\n";

DB::table('transactions')->where('id', $id)->delete();
echo "Done.\n";
