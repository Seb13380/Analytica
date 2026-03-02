<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check a sample of meta in transactions
echo "=== Sample meta values ===\n";
$sample = DB::select("SELECT id, date, amount, meta FROM transactions ORDER BY date LIMIT 5");
foreach ($sample as $r) {
    echo "{$r->date} | {$r->amount} | meta={$r->meta}\n";
}

echo "\n=== Early transactions (before 2020-12) ===\n";
$early = DB::select("SELECT id, date, amount, label FROM transactions WHERE date < '2020-12-01' ORDER BY date LIMIT 30");
foreach ($early as $r) {
    echo "{$r->date} | {$r->amount} | {$r->label}\n";
}
echo "Total: ".DB::selectOne("SELECT COUNT(*) AS n FROM transactions WHERE date < '2020-12-01'")->n."\n";

echo "\n=== All statements ===\n";
$stmts = DB::select("SELECT id, bank_account_id, import_status, transactions_imported, original_filename FROM statements ORDER BY id");
foreach ($stmts as $r) {
    echo "stmt={$r->id} | acct_id={$r->bank_account_id} | file={$r->original_filename}\n";
}

echo "\n=== Distribution par année totale ===\n";
$rows = DB::select("SELECT EXTRACT(YEAR FROM date)::int AS y, COUNT(*) AS n, MIN(date) dmin, MAX(date) dmax FROM transactions GROUP BY y ORDER BY y");
foreach ($rows as $r) {
    echo "{$r->y}: {$r->n} tx [{$r->dmin}..{$r->dmax}]\n";
}
echo "Total: ".DB::selectOne("SELECT COUNT(*) AS n FROM transactions")->n."\n";
