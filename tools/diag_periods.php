<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Périodes de relevé distinctes dans meta ===\n";
$periods = DB::select("
    SELECT 
        meta->>'statement_period' AS period,
        (meta->'statement_period'->>'start') AS period_start,
        (meta->'statement_period'->>'end') AS period_end,
        COUNT(*) AS n,
        MIN(date) AS dmin,
        MAX(date) AS dmax
    FROM transactions
    GROUP BY period, period_start, period_end
    ORDER BY period_start
");
foreach ($periods as $r) {
    echo "period={$r->period_start}..{$r->period_end} | {$r->n} tx | dates: {$r->dmin}..{$r->dmax}\n";
}

echo "\n=== Transactions avant 2020-12: quelles periodes? ===\n";
$early = DB::select("
    SELECT 
        (meta->'statement_period'->>'start') AS pstart,
        (meta->'statement_period'->>'end') AS pend,
        COUNT(*) AS n,
        MIN(date) dmin,
        MAX(date) dmax
    FROM transactions
    WHERE date < '2020-12-01'
    GROUP BY pstart, pend
    ORDER BY pstart
");
foreach ($early as $r) {
    echo "period={$r->pstart}..{$r->pend} | {$r->n} tx | {$r->dmin}..{$r->dmax}\n";
}

echo "\n=== Labels/titulaire des early transactions - patterns ===\n";
$labels = DB::select("
    SELECT label, date, amount,
        (meta->'statement_period'->>'start') AS pstart
    FROM transactions
    WHERE date < '2020-12-01'
    ORDER BY date
    LIMIT 20
");
foreach ($labels as $r) {
    // Check if label mentions account holder patterns
    echo "{$r->date} [{$r->pstart}] {$r->amount} | ".substr($r->label, 0, 120)."\n";
}
