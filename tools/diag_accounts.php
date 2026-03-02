<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Transactions avant 2020-12-01 ===\n";
$early = DB::select("SELECT id, date, amount, label, account_number, statement_id FROM transactions WHERE date < '2020-12-01' ORDER BY date LIMIT 50");
foreach ($early as $r) {
    echo "{$r->date} | {$r->amount} | stmt={$r->statement_id} | acct={$r->account_number} | {$r->label}\n";
}
echo "Total avant 2020-12: ".count($early)."\n\n";

echo "=== Comptes distincts dans transactions ===\n";
$accounts = DB::select("SELECT account_number, COUNT(*) AS n, MIN(date) AS dmin, MAX(date) AS dmax, s.id AS stmt_id FROM transactions t LEFT JOIN statements s ON s.id = t.statement_id GROUP BY account_number, s.id ORDER BY account_number, s.id");
foreach ($accounts as $r) {
    echo "acct={$r->account_number} | stmt={$r->stmt_id} | {$r->n} tx | {$r->dmin}..{$r->dmax}\n";
}

echo "\n=== Comptes dans la table statements ===\n";
$stmts = DB::select("SELECT id, account_number, account_holder, import_status, transactions_imported FROM statements ORDER BY id");
foreach ($stmts as $r) {
    echo "stmt={$r->id} | acct={$r->account_number} | holder={$r->account_holder} | status={$r->import_status} | tx={$r->transactions_imported}\n";
}
