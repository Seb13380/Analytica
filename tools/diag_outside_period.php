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

$rows = DB::table('transactions')
    ->whereIn('bank_account_id', $accountIds)
    ->whereBetween('date', [$start, $end])
    ->whereRaw("(meta->'quality_flags')::text ILIKE '%outside_statement_period%'")
    ->selectRaw("id, date::date as d, type, ABS(amount) as a, LEFT(normalized_label, 90) as lbl, (meta->'statement_period'->>'start') as p_start, (meta->'statement_period'->>'end') as p_end")
    ->orderBy('date')
    ->limit(120)
    ->get();

$total = DB::table('transactions')
    ->whereIn('bank_account_id', $accountIds)
    ->whereBetween('date', [$start, $end])
    ->whereRaw("(meta->'quality_flags')::text ILIKE '%outside_statement_period%'")
    ->count();

$sumCredits = (float) DB::table('transactions')
    ->whereIn('bank_account_id', $accountIds)
    ->whereBetween('date', [$start, $end])
    ->where('type', 'credit')
    ->whereRaw("(meta->'quality_flags')::text ILIKE '%outside_statement_period%'")
    ->sum(DB::raw('ABS(amount)'));

$sumDebits = (float) DB::table('transactions')
    ->whereIn('bank_account_id', $accountIds)
    ->whereBetween('date', [$start, $end])
    ->where('type', 'debit')
    ->whereRaw("(meta->'quality_flags')::text ILIKE '%outside_statement_period%'")
    ->sum(DB::raw('ABS(amount)'));

echo "Case={$caseId} Year={$year}\n";
echo "Rows flagged outside_statement_period: {$total}\n";
echo "Credits flagged: ".number_format($sumCredits,2,',',' ')." €\n";
echo "Debits flagged: ".number_format($sumDebits,2,',',' ')." €\n";

foreach ($rows as $r) {
    echo sprintf("%s | %s | %.2f | period %s..%s | %s\n", $r->d, $r->type, (float)$r->a, (string)$r->p_start, (string)$r->p_end, (string)$r->lbl);
}
