<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Distribution account_section dans meta ===\n";
$sections = DB::select("
    SELECT 
        meta->>'account_section' AS section,
        COUNT(*) AS n,
        MIN(date) dmin,
        MAX(date) dmax
    FROM transactions
    GROUP BY section
    ORDER BY section
");
foreach ($sections as $r) {
    echo "section={$r->section} | {$r->n} tx | {$r->dmin}..{$r->dmax}\n";
}

echo "\n=== Distribution par année ===\n";
$rows = DB::select("SELECT EXTRACT(YEAR FROM date)::int AS y, COUNT(*) AS n FROM transactions GROUP BY y ORDER BY y");
foreach ($rows as $r) {
    echo "{$r->y}: {$r->n} tx\n";
}
echo "Total: ".DB::selectOne("SELECT COUNT(*) AS n FROM transactions")->n."\n";

echo "\n=== Transactions avant 2020-12-01 ===\n";
$early = DB::select("SELECT date, amount, meta->>'account_section' AS section, label FROM transactions WHERE date < '2020-12-01' ORDER BY date LIMIT 5");
foreach ($early as $r) {
    echo "{$r->date} | {$r->amount} | section={$r->section} | {$r->label}\n";
}
echo "Total avant 2020-12: ".DB::selectOne("SELECT COUNT(*) AS n FROM transactions WHERE date < '2020-12-01'")->n."\n";
