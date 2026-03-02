<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Transactions avant 2020-12-01 (toutes) ===\n";
$early = DB::select("SELECT t.id, t.date, t.amount, t.label, s.id AS stmt_id, s.original_filename FROM transactions t JOIN statements s ON s.id=t.meta::jsonb->>'statement_id' WHERE t.date < '2020-12-01' ORDER BY t.date LIMIT 50");
foreach ($early as $r) {
    echo "{$r->date} | {$r->amount} | stmt={$r->stmt_id} | {$r->original_filename} | {$r->label}\n";
}

echo "\n=== Via meta jsonb ===\n";
// Try without JOIN - just check meta field
$sample = DB::select("SELECT id, date, amount, meta FROM transactions WHERE date < '2020-12-01' ORDER BY date LIMIT 10");
foreach ($sample as $r) {
    echo "{$r->date} | {$r->amount} | meta={$r->meta}\n";
}

echo "\nTotal avant 2020-12: ".DB::selectOne("SELECT COUNT(*) AS n FROM transactions WHERE date < '2020-12-01'")->n."\n";

echo "\n=== Distribution par année et statement ===\n";
$rows = DB::select("
    SELECT 
        (meta->>'statement_id')::int AS stmt_id,
        EXTRACT(YEAR FROM date)::int AS y,
        COUNT(*) AS n
    FROM transactions
    GROUP BY stmt_id, y
    ORDER BY stmt_id, y
");
foreach ($rows as $r) {
    echo "stmt={$r->stmt_id} | {$r->y}: {$r->n} tx\n";
}
