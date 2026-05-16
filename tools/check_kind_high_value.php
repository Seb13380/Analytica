<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rows = \Illuminate\Support\Facades\DB::select("
    SELECT date, amount, type, kind, LEFT(label, 50) as lbl
    FROM transactions
    WHERE ABS(amount) >= 5000 AND type='debit'
    ORDER BY ABS(amount) DESC
    LIMIT 15
");
foreach ($rows as $r) {
    echo implode(' | ', [$r->date, number_format(abs($r->amount), 0, ',', ' ').'€', $r->type, $r->kind ?? 'NULL', $r->lbl]) . "\n";
}
