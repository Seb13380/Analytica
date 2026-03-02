<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$amount = 23496.00;

$rows = DB::table('transactions as t')
    ->join('bank_accounts as ba', 'ba.id', '=', 't.bank_account_id')
    ->where('ba.case_id', 1)
    ->where('t.amount', $amount)
    ->orderBy('t.date')
    ->select('t.id', 't.bank_account_id', 't.date', 't.amount', 't.type', 't.label', 't.normalized_label')
    ->get();

echo "=== Transactions with amount " . $amount . " ===\n";
foreach ($rows as $r) {
    echo "id={$r->id} | acct={$r->bank_account_id} | {$r->date} | {$r->type} | " . substr($r->label, 0, 100) . "\n";
    echo "   normalized: " . substr($r->normalized_label, 0, 80) . "\n";
}
echo "Total: " . count($rows) . "\n";
