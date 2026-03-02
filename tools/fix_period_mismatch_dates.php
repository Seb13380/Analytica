<?php

require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;

$monthMap = [
    'janvier' => 1,
    'fevrier' => 2,
    'février' => 2,
    'mars' => 3,
    'avril' => 4,
    'mai' => 5,
    'juin' => 6,
    'juillet' => 7,
    'aout' => 8,
    'août' => 8,
    'septembre' => 9,
    'octobre' => 10,
    'novembre' => 11,
    'decembre' => 12,
    'décembre' => 12,
];

$rx = '/du\s+([0-3]?\d)\s+(janvier|fevrier|février|mars|avril|mai|juin|juillet|aout|août|septembre|octobre|novembre|decembre|décembre)\s+(20\d{2})\s+au\s+([0-3]?\d)\s+(janvier|fevrier|février|mars|avril|mai|juin|juillet|aout|août|septembre|octobre|novembre|decembre|décembre)\s+(20\d{2})/iu';

$rows = Transaction::query()
    ->where('label', 'ilike', '%du %')
    ->where('label', 'ilike', '% au %')
    ->whereRaw("label ~* '20[0-9]{2}'")
    ->orderBy('id')
    ->get(['id', 'date', 'label']);

$checked = 0;
$fixed = 0;
$skippedDuplicates = 0;

foreach ($rows as $tx) {
    $label = (string) ($tx->label ?? '');
    if (!preg_match($rx, $label, $m)) {
        continue;
    }

    $startDay = (int) $m[1];
    $startMonthName = mb_strtolower((string) $m[2]);
    $startYear = (int) $m[3];
    $endDay = (int) $m[4];
    $endMonthName = mb_strtolower((string) $m[5]);
    $endYear = (int) $m[6];

    if (!isset($monthMap[$startMonthName], $monthMap[$endMonthName])) {
        continue;
    }

    $checked++;

    try {
        $periodStart = Carbon::create($startYear, $monthMap[$startMonthName], $startDay, 0, 0, 0);
        $periodEnd = Carbon::create($endYear, $monthMap[$endMonthName], $endDay, 23, 59, 59);
        $txDate = Carbon::parse((string) $tx->date);
    } catch (\Throwable) {
        continue;
    }

    if ($txDate->betweenIncluded($periodStart->copy()->subDays(15), $periodEnd->copy()->addDays(15))) {
        continue;
    }

    $day = (int) $txDate->format('d');
    $month = (int) $txDate->format('m');

    $candidateYears = array_values(array_unique([$startYear, $endYear]));
    $bestDate = null;
    $bestScore = PHP_INT_MAX;

    foreach ($candidateYears as $year) {
        try {
            $candidate = Carbon::create($year, $month, $day, 0, 0, 0);
        } catch (\Throwable) {
            continue;
        }

        if ($candidate->betweenIncluded($periodStart->copy()->subDays(20), $periodEnd->copy()->addDays(20))) {
            $score = abs($candidate->diffInDays($periodStart, false));
            if ($score < $bestScore) {
                $bestScore = $score;
                $bestDate = $candidate;
            }
        }
    }

    if ($bestDate === null) {
        continue;
    }

    $oldDate = (string) $tx->date;
    $newDate = $bestDate->format('Y-m-d');
    if ($oldDate === $newDate) {
        continue;
    }

    try {
        $tx->date = $newDate;
        $tx->save();
        $fixed++;
        echo sprintf("Fixed #%d: %s -> %s\n", $tx->id, $oldDate, $newDate);
    } catch (UniqueConstraintViolationException) {
        $skippedDuplicates++;
        echo sprintf("Skipped duplicate #%d: %s -> %s\n", $tx->id, $oldDate, $newDate);
    }
}

echo "\nChecked with explicit period: {$checked}\n";
echo "Fixed dates: {$fixed}\n";
echo "Skipped duplicates: {$skippedDuplicates}\n";
