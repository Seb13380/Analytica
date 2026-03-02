<?php

require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\BankAccount;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;

$caseId = isset($argv[1]) ? (int) $argv[1] : 1;
$cutoff = isset($argv[2]) ? (string) $argv[2] : '2020-12-22';

$accountIds = BankAccount::query()
    ->where('case_id', $caseId)
    ->pluck('id');

if ($accountIds->isEmpty()) {
    echo "No bank accounts for case #{$caseId}\n";
    exit(0);
}

$rows = Transaction::query()
    ->whereIn('bank_account_id', $accountIds)
    ->whereDate('date', '>=', '2020-01-01')
    ->whereDate('date', '<', $cutoff)
    ->orderBy('date')
    ->orderBy('id')
    ->get(['id', 'bank_account_id', 'date', 'amount', 'type', 'normalized_label']);

$checked = 0;
$updated = 0;
$deletedAsDuplicate = 0;
$skipped = 0;

foreach ($rows as $tx) {
    $checked++;

    try {
        $newDate = Carbon::parse((string) $tx->date)->addYear()->toDateString();
    } catch (\Throwable) {
        $skipped++;
        continue;
    }

    $oldDate = (string) $tx->date;
    if ($oldDate === $newDate) {
        $skipped++;
        continue;
    }

    try {
        $tx->date = $newDate;
        $tx->save();
        $updated++;
    } catch (UniqueConstraintViolationException) {
        $duplicate = Transaction::query()
            ->where('id', '<>', $tx->id)
            ->where('bank_account_id', $tx->bank_account_id)
            ->whereDate('date', $newDate)
            ->where('amount', $tx->amount)
            ->where('type', $tx->type)
            ->where('normalized_label', $tx->normalized_label)
            ->first();

        if ($duplicate) {
            $tx->delete();
            $deletedAsDuplicate++;
        } else {
            $skipped++;
        }
    }
}

echo "Case: {$caseId}\n";
echo "Cutoff: {$cutoff}\n";
echo "Checked: {$checked}\n";
echo "Updated: {$updated}\n";
echo "Deleted duplicates: {$deletedAsDuplicate}\n";
echo "Skipped: {$skipped}\n";
