<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$total = \DB::table('transactions')->count();
$accts = \DB::table('bank_accounts')->get();
echo "Total transactions: $total\n";
echo "Bank accounts: " . $accts->count() . "\n";
foreach ($accts as $a) {
    $cnt = \DB::table('transactions')->where('bank_account_id', $a->id)->count();
    echo "  id={$a->id} holder=" . ($a->account_holder ?? 'null') . " → $cnt tx\n";
}

// Check account_section distribution
$sections = \DB::table('transactions')
    ->selectRaw("meta->>'account_section' as section, count(*) as cnt")
    ->groupByRaw("meta->>'account_section'")
    ->get();
echo "\nAccount sections:\n";
foreach ($sections as $s) {
    echo "  section=" . ($s->section ?? 'null') . " | {$s->cnt} tx\n";
}
