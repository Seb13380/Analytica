<?php

require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\Models\BankAccount;
use Illuminate\Support\Facades\DB;

$caseId = isset($argv[1]) ? (int) $argv[1] : 1;
$year = isset($argv[2]) ? (int) $argv[2] : 2021;

$start = sprintf('%04d-01-01', $year);
$end = sprintf('%04d-12-31', $year);

$accounts = BankAccount::query()->where('case_id', $caseId)->get(['id','bank_name','iban_masked','account_holder']);
if ($accounts->isEmpty()) {
    echo "No accounts for case #{$caseId}\n";
    exit(0);
}

echo "Case {$caseId} | Year {$year}\n";

foreach ($accounts as $acc) {
    $credits = (float) DB::table('transactions')
        ->where('bank_account_id', $acc->id)
        ->whereBetween('date', [$start, $end])
        ->where('type', 'credit')
        ->sum(DB::raw('ABS(amount)'));

    $debits = (float) DB::table('transactions')
        ->where('bank_account_id', $acc->id)
        ->whereBetween('date', [$start, $end])
        ->where('type', 'debit')
        ->sum(DB::raw('ABS(amount)'));

    $count = (int) DB::table('transactions')
        ->where('bank_account_id', $acc->id)
        ->whereBetween('date', [$start, $end])
        ->count();

    echo sprintf(
        "- account_id=%d | bank=%s | holder=%s | credits=%s | debits=%s | net=%s | tx=%d\n",
        (int) $acc->id,
        (string) ($acc->bank_name ?? 'N/A'),
        (string) ($acc->account_holder ?? 'N/A'),
        number_format($credits, 2, ',', ' '),
        number_format($debits, 2, ',', ' '),
        number_format($credits - $debits, 2, ',', ' '),
        $count
    );
}
