<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Show duplicates: same bank_account_id + date + amount + type
// Only flag groups where ALL labels are actually similar (OCR variants of same transaction).
$groups = \DB::select("
    SELECT
        t.bank_account_id,
        t.date::text,
        t.amount,
        t.type,
        count(*) as cnt,
        string_agg(t.id::text, ', ' ORDER BY t.id) as ids,
        array_agg(coalesce(t.normalized_label, t.label, '') ORDER BY t.id) as labels
    FROM transactions t
    WHERE t.bank_account_id = 1
    GROUP BY t.bank_account_id, t.date, t.amount, t.type
    HAVING count(*) > 1
    ORDER BY count(*) DESC, t.date
");

$trueDups = 0;
$ambiguous = 0;

foreach ($groups as $r) {
    $labels = array_map('trim', str_getcsv(trim($r->labels, '{}')));
    // Compare all pairs: if any two are very different, it's not a true duplicate
    $allSimilar = true;
    for ($i = 0; $i < count($labels); $i++) {
        for ($j = $i + 1; $j < count($labels); $j++) {
            $a = mb_strtolower(mb_substr($labels[$i], 0, 40));
            $b = mb_strtolower(mb_substr($labels[$j], 0, 40));
            similar_text($a, $b, $pct);
            if ($pct < 45) {
                $allSimilar = false;
                break 2;
            }
        }
    }
    if ($allSimilar) {
        $trueDups++;
        echo "[DUPLICATE] {$r->date} | " . number_format((float)$r->amount, 2) . " | {$r->type} | {$r->cnt}x | ids=[{$r->ids}]\n";
        foreach ($labels as $l) echo "  label: " . mb_substr($l, 0, 80) . "\n";
    } else {
        $ambiguous++;
        echo "[DISTINCT]  {$r->date} | " . number_format((float)$r->amount, 2) . " | {$r->type} | {$r->cnt}x | ids=[{$r->ids}]\n";
        foreach ($labels as $l) echo "  label: " . mb_substr($l, 0, 80) . "\n";
    }
}

echo "\nTrue duplicates: $trueDups | Distinct tx same date+amt: $ambiguous\n";
echo "allow_db_dedup_heuristics = " . (config('analytica.import.allow_db_dedup_heuristics') ? 'true' : 'false') . "\n";

