<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rows = App\Models\Transaction::query()
    ->whereRaw('ABS(amount) = 18000')
    ->where('type', 'debit')
    ->orderBy('date')
    ->get(['id','date','amount','type','normalized_label','bank_account_id','label']);

echo "=== Transactions 18000€ débit ===\n";
foreach ($rows as $r) {
    echo sprintf("id=%d | date=%s | acct=%d | label=%s\n",
        $r->id, $r->date, $r->bank_account_id, substr((string)$r->normalized_label, 0, 100));
}

echo "\n=== Transactions avec CEL (débit) ===\n";
$cel = App\Models\Transaction::query()
    ->where('normalized_label', 'LIKE', '%CEL%')
    ->where('type', 'debit')
    ->orderBy('date')
    ->get(['id','date','amount','type','normalized_label','bank_account_id']);

foreach ($cel as $r) {
    echo sprintf("id=%d | date=%s | amt=%.2f | acct=%d | label=%s\n",
        $r->id, $r->date, $r->amount, $r->bank_account_id, substr((string)$r->normalized_label, 0, 100));
}
