<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Force-delete remaining duplicates (same date + amount + type, keep lowest id regardless of label)
$groups = \DB::select("
    SELECT
        bank_account_id,
        date::text AS d,
        amount,
        type,
        array_agg(id ORDER BY id) AS ids
    FROM transactions
    GROUP BY bank_account_id, date, amount, type
    HAVING count(*) > 1
");

echo "Remaining duplicate groups: " . count($groups) . "\n";
$totalDeleted = 0;
foreach ($groups as $g) {
    $ids = array_map('intval', str_getcsv(trim($g->ids, '{}')));
    $keepId = min($ids);
    $deleteIds = array_filter($ids, fn($id) => $id !== $keepId);

    // Show labels for transparency
    $txs = \App\Models\Transaction::whereIn('id', $ids)->get(['id', 'label']);
    echo "  Keeping id={$keepId}, deleting [" . implode(',', $deleteIds) . "] | {$g->d} {$g->amount} {$g->type}\n";
    foreach ($txs as $tx) {
        echo "    [{$tx->id}] " . substr($tx->label, 0, 70) . "\n";
    }

    $deleted = \App\Models\Transaction::whereIn('id', $deleteIds)->delete();
    $totalDeleted += $deleted;
}

echo "\nTotal deleted: $totalDeleted\n";
echo "Remaining transactions: " . \DB::table('transactions')->count() . "\n";
