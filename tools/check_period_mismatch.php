<?php

require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Transaction;
use Carbon\Carbon;

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
    ->orderByDesc('id')
    ->limit(25000)
    ->get(['id', 'date', 'label', 'amount', 'type']);

$totalWithPeriod = 0;
$mismatch = [];

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

    $totalWithPeriod++;

    try {
        $periodStart = Carbon::create($startYear, $monthMap[$startMonthName], $startDay, 0, 0, 0);
        $periodEnd = Carbon::create($endYear, $monthMap[$endMonthName], $endDay, 23, 59, 59);
        $txDate = Carbon::parse((string) $tx->date);

        $inside = $txDate->betweenIncluded($periodStart->copy()->subDays(15), $periodEnd->copy()->addDays(15));

        if (!$inside) {
            $mismatch[] = [
                'id' => $tx->id,
                'date' => (string) $tx->date,
                'amount' => (float) $tx->amount,
                'type' => (string) $tx->type,
                'period' => $periodStart->format('Y-m-d').' -> '.$periodEnd->format('Y-m-d'),
                'label' => mb_substr($label, 0, 260),
            ];
        }
    } catch (\Throwable) {
        continue;
    }
}

echo "=== Period mismatch diagnostic ===\n";
echo "Rows scanned with explicit period in label: {$totalWithPeriod}\n";
echo "Mismatches found: ".count($mismatch)."\n\n";

foreach (array_slice($mismatch, 0, 30) as $row) {
    echo sprintf(
        "#%d | %s | %s | %.2f | period %s\n  %s\n",
        $row['id'],
        $row['date'],
        $row['type'],
        $row['amount'],
        $row['period'],
        $row['label']
    );
}
