<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== bank_accounts ===\n";
$accts = DB::select("SELECT id, case_id, account_number, account_holder, bank_name, account_type FROM bank_accounts ORDER BY id");
foreach ($accts as $a) {
    echo "id={$a->id} | case={$a->case_id} | num={$a->account_number} | holder={$a->account_holder} | bank={$a->bank_name} | type={$a->account_type}\n";
}

echo "\n=== Distribution par bank_account_id et année ===\n";
$rows = DB::select("SELECT bank_account_id, EXTRACT(YEAR FROM date)::int AS y, COUNT(*) AS n FROM transactions GROUP BY bank_account_id, y ORDER BY bank_account_id, y");
foreach ($rows as $r) {
    echo "acct_id={$r->bank_account_id} | {$r->y}: {$r->n} tx\n";
}

echo "\n=== Transactions avant 2020-12-01 ===\n";
$early = DB::select("SELECT t.id, t.date, t.amount, t.bank_account_id, t.label FROM transactions t WHERE t.date < '2020-12-01' ORDER BY t.date LIMIT 30");
foreach ($early as $r) {
    echo "{$r->date} | {$r->amount} | acct_id={$r->bank_account_id} | {$r->label}\n";
}
echo "Total avant 2020-12: ".count($early)."\n";

echo "\n=== Statements par bank_account_id ===\n";
$stmts = DB::select("SELECT id, bank_account_id, import_status, transactions_imported, original_filename FROM statements ORDER BY id");
foreach ($stmts as $r) {
    echo "stmt={$r->id} | acct_id={$r->bank_account_id} | status={$r->import_status} | tx={$r->transactions_imported} | file={$r->original_filename}\n";
}
