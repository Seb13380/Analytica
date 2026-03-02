<?php

require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\Models\BankAccount;
use Illuminate\Support\Facades\DB;

$caseId = isset($argv[1]) ? (int) $argv[1] : 1;
$accountIds = BankAccount::query()->where('case_id', $caseId)->pluck('id');

if ($accountIds->isEmpty()) {
    echo "No accounts for case #{$caseId}\n";
    exit(0);
}

$rows = DB::table('transactions')
    ->whereIn('bank_account_id', $accountIds)
    ->selectRaw("EXTRACT(YEAR FROM date)::int as y")
    ->selectRaw("SUM(CASE WHEN type='credit' THEN ABS(amount) ELSE 0 END) as credits")
    ->selectRaw("SUM(CASE WHEN type='debit' THEN ABS(amount) ELSE 0 END) as debits")
    ->selectRaw("COUNT(*) as cnt")
    ->groupByRaw("EXTRACT(YEAR FROM date)")
    ->orderBy('y')
    ->get();

foreach ($rows as $r) {
    $net = (float) $r->credits - (float) $r->debits;
    echo sprintf(
        "%d | credits=%s | debits=%s | net=%s | tx=%d\n",
        (int) $r->y,
        number_format((float) $r->credits, 2, ',', ' '),
        number_format((float) $r->debits, 2, ',', ' '),
        number_format($net, 2, ',', ' '),
        (int) $r->cnt
    );
}
