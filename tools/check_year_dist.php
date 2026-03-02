<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$rows = DB::select('SELECT EXTRACT(YEAR FROM date)::int AS y, COUNT(*) AS n, MIN(date) AS dmin, MAX(date) AS dmax FROM transactions GROUP BY y ORDER BY y');
foreach ($rows as $r) {
    echo "{$r->y}: {$r->n} tx [{$r->dmin}..{$r->dmax}]\n";
}
$total = DB::selectOne('SELECT COUNT(*) AS n FROM transactions');
echo "Total: {$total->n}\n";

// Check the big 2021 transactions
echo "\n--- Big transactions >= 10000 by year ---\n";
$big = DB::select("SELECT EXTRACT(YEAR FROM date)::int AS y, date, amount, label FROM transactions WHERE ABS(amount) >= 10000 ORDER BY y, date");
foreach ($big as $r) {
    echo "{$r->y} | {$r->date} | {$r->amount} | {$r->label}\n";
}
