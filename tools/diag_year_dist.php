<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== JOINT ACCOUNT (id=1) ===\n";
$rows = \DB::table('transactions')
    ->where('bank_account_id', 1)
    ->selectRaw("to_char(date, 'YYYY-MM') as month, count(*) as cnt")
    ->groupByRaw("to_char(date, 'YYYY-MM')")
    ->orderBy('month')
    ->get();
foreach ($rows as $r) echo "  {$r->month} → {$r->cnt} tx\n";

echo "\n=== PERSONAL ACCOUNT (id=2) ===\n";
$rows2 = \DB::table('transactions')
    ->where('bank_account_id', 2)
    ->selectRaw("to_char(date, 'YYYY-MM') as month, count(*) as cnt")
    ->groupByRaw("to_char(date, 'YYYY-MM')")
    ->orderBy('month')
    ->get();
foreach ($rows2 as $r) echo "  {$r->month} → {$r->cnt} tx\n";

echo "\n=== SAVINGS ACCOUNT (id=3) ===\n";
$rows3 = \DB::table('transactions')
    ->where('bank_account_id', 3)
    ->selectRaw("to_char(date, 'YYYY-MM') as month, count(*) as cnt")
    ->groupByRaw("to_char(date, 'YYYY-MM')")
    ->orderBy('month')
    ->get();
foreach ($rows3 as $r) echo "  {$r->month} → {$r->cnt} tx\n";

echo "\n=== PRE DEC-2020 CHECK ===\n";
$early = \DB::table('transactions')
    ->where('date', '<', '2020-12-01')
    ->selectRaw("bank_account_id, count(*) as cnt, min(date) as earliest")
    ->groupBy('bank_account_id')
    ->get();
if ($early->isEmpty()) {
    echo "No pre-Dec-2020 transactions in any account. ✓\n";
} else {
    foreach ($early as $r) {
        echo "  account_id={$r->bank_account_id} → {$r->cnt} tx, earliest={$r->earliest}\n";
    }
}
