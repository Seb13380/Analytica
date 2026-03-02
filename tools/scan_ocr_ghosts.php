<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

/**
 * Scan for OCR date-ghost duplicates:
 * Same amount + type + very similar label, but dates differ by 1-9 days.
 * This catches OCR digit confusions: 3↔8, 1↔7, 0↔6, 5↔6, etc.
 */

$rows = DB::table('transactions as t')
    ->join('bank_accounts as ba', 'ba.id', '=', 't.bank_account_id')
    ->where('ba.case_id', 1)
    ->select('t.id', 't.bank_account_id', 't.date', 't.amount', 't.type', 't.normalized_label', 't.label')
    ->orderBy('t.amount')
    ->orderBy('t.date')
    ->get();

// Group by account + amount + type
$groups = [];
foreach ($rows as $r) {
    $key = $r->bank_account_id . '|' . $r->amount . '|' . $r->type;
    $groups[$key][] = $r;
}

$suspects = [];

foreach ($groups as $key => $txs) {
    if (count($txs) < 2) continue;

    for ($i = 0; $i < count($txs); $i++) {
        for ($j = $i + 1; $j < count($txs); $j++) {
            $a = $txs[$i];
            $b = $txs[$j];

            $dA = new DateTime($a->date);
            $dB = new DateTime($b->date);
            $diffDays = abs($dA->diff($dB)->days);

            // Only check pairs within 9 days of each other
            if ($diffDays < 1 || $diffDays > 9) continue;

            // Compare normalized labels
            $la = $a->normalized_label ?? '';
            $lb = $b->normalized_label ?? '';
            $maxLen = max(strlen($la), strlen($lb));
            if ($maxLen === 0) continue;

            $dist = levenshtein(substr($la, 0, 100), substr($lb, 0, 100));
            $similarity = 1 - ($dist / max(strlen(substr($la, 0, 100)), strlen(substr($lb, 0, 100))));

            // High similarity = likely same transaction with OCR date error
            if ($similarity >= 0.75) {
                $suspects[] = [
                    'a' => $a,
                    'b' => $b,
                    'diff_days' => $diffDays,
                    'similarity' => round($similarity * 100, 1),
                ];
            }
        }
    }
}

if (empty($suspects)) {
    echo "No OCR date-ghost duplicates found.\n";
    exit;
}

echo "=== Potential OCR date-ghost duplicates ===\n";
echo "Criteria: same amount+type, dates differ 1-9 days, label similarity ≥75%\n\n";

foreach ($suspects as $idx => $s) {
    $a = $s['a'];
    $b = $s['b'];
    echo str_repeat('-', 80) . "\n";
    echo "Pair #" . ($idx + 1) . " | diff=" . $s['diff_days'] . "d | similarity=" . $s['similarity'] . "%\n";
    echo "  A: id={$a->id} | {$a->date} | {$a->type} | {$a->amount}€\n";
    echo "     " . substr($a->label, 0, 100) . "\n";
    echo "  B: id={$b->id} | {$b->date} | {$b->type} | {$b->amount}€\n";
    echo "     " . substr($b->label, 0, 100) . "\n";
}

echo "\nTotal suspects: " . count($suspects) . "\n";
echo "\nTo delete a ghost, run:\n";
echo "  docker compose exec app php tools/delete_tx.php <id>\n";
