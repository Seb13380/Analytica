<?php

require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$caseId = isset($argv[1]) ? (int) $argv[1] : 1;
$year = isset($argv[2]) ? (int) $argv[2] : 2021;

$start = sprintf('%04d-01-01', $year);
$end = sprintf('%04d-12-31', $year);

$accountIds = DB::table('bank_accounts')->where('case_id', $caseId)->pluck('id');
if ($accountIds->isEmpty()) {
    echo "No bank accounts for case #{$caseId}\n";
    exit(0);
}

$totalCredits = (float) DB::table('transactions')
    ->whereIn('bank_account_id', $accountIds)
    ->whereBetween('date', [$start, $end])
    ->where('type', 'credit')
    ->sum(DB::raw('ABS(amount)'));

$groups = DB::table('transactions')
    ->selectRaw("date::date as d, type, ABS(amount) as a, COUNT(*) as c, SUM(ABS(amount)) as s")
    ->whereIn('bank_account_id', $accountIds)
    ->whereBetween('date', [$start, $end])
    ->groupByRaw("date::date, type, ABS(amount)")
    ->havingRaw('COUNT(*) > 1')
    ->orderByDesc('s')
    ->get();

$potentialExtraCredits = 0.0;

foreach ($groups as $g) {
    if ((string) $g->type === 'credit') {
        $potentialExtraCredits += ((float) $g->a) * (((int) $g->c) - 1);
    }
}

echo "Case: {$caseId}\n";
echo "Year: {$year}\n";
echo "Total credits: ".number_format($totalCredits, 2, ',', ' ')." €\n";
echo "Repeated date+type+amount groups (top 50 shown):\n";

foreach ($groups->take(50) as $g) {
    echo sprintf("  %s | %s | %.2f | x%d\n", $g->d, $g->type, (float) $g->a, (int) $g->c);
}

echo "Potential extra credits (collision model): ".number_format($potentialExtraCredits, 2, ',', ' ')." €\n";
