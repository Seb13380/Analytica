<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Find all groups with exact duplicates: same bank_account_id + date + amount + type
// Keep the LOWEST id (first imported), delete the rest.

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

echo "Duplicate groups found: " . count($groups) . "\n";

$totalDeleted = 0;
foreach ($groups as $g) {
    $ids = array_map('intval', str_getcsv(trim($g->ids, '{}')));
    $keepId = min($ids);
    $deleteIds = array_filter($ids, fn($id) => $id !== $keepId);

    // Extra safety: only delete if labels are similar (same normalized prefix)
    $txs = \App\Models\Transaction::whereIn('id', $ids)->get(['id', 'normalized_label', 'label']);
    $labels = $txs->pluck('normalized_label')->filter()->values();
    if ($labels->count() > 1) {
        $first = mb_substr((string)$labels[0], 0, 20);
        $allSimilar = $labels->every(fn($l) => mb_substr($l, 0, 20) === $first || similar_text($first, mb_substr($l, 0, 20)) >= 12);
        if (!$allSimilar) {
            echo "  SKIP (labels differ) date={$g->d} amt={$g->amount} ids=[" . implode(',', $ids) . "]\n";
            continue;
        }
    }

    $deleted = \App\Models\Transaction::whereIn('id', $deleteIds)->delete();
    $totalDeleted += $deleted;
    echo "  Kept id={$keepId}, deleted [" . implode(',', $deleteIds) . "] | date={$g->d} amt={$g->amount} type={$g->type}\n";
}

echo "\nTotal deleted: $totalDeleted duplicates.\n";

// Final count
echo "Remaining transactions: " . \DB::table('transactions')->count() . "\n";
