<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== PRE-DEC-2020 IN JOINT ACCOUNT (id=1) - BY STATEMENT SOURCE ===\n";
$rows = \DB::table('transactions as t')
    ->where('t.bank_account_id', 1)
    ->where('t.date', '<', '2020-12-01')
    ->selectRaw("t.meta->>'statement_id' as stmt_id, t.meta->>'account_section' as section, count(*) as cnt, min(t.date::text) as earliest, max(t.date::text) as latest")
    ->groupByRaw("t.meta->>'statement_id', t.meta->>'account_section'")
    ->orderByRaw("t.meta->>'statement_id'")
    ->get();

foreach ($rows as $r) {
    echo "  stmt_id={$r->stmt_id} | section={$r->section} | {$r->cnt} tx | {$r->earliest} .. {$r->latest}\n";
}

echo "\n=== SAMPLE PRE-DEC JOINT TRANSACTIONS ===\n";
$samples = \DB::table('transactions')
    ->where('bank_account_id', 1)
    ->where('date', '<', '2020-12-01')
    ->orderBy('date')
    ->limit(10)
    ->get(['id', 'date', 'amount', 'label', 'meta']);

foreach ($samples as $tx) {
    $meta = json_decode((string)($tx->meta ?? '{}'), true);
    echo "  {$tx->date} | " . number_format((float)$tx->amount, 2) . " | {$tx->label} | section=" . ($meta['account_section'] ?? '?') . "\n";
}
