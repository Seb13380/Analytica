<?php

require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\Models\BankAccount;
use App\Models\Transaction;

$caseId = isset($argv[1]) ? (int) $argv[1] : 1;
$dryRun = !in_array('--execute', $argv ?? [], true);

echo $dryRun ? "=== DRY-RUN dedupe_2021_against_2020 ===\n" : "=== EXECUTE dedupe_2021_against_2020 ===\n";

$accountIds = BankAccount::query()->where('case_id', $caseId)->pluck('id');
if ($accountIds->isEmpty()) {
    echo "No accounts for case #{$caseId}\n";
    exit(0);
}

$rows2021 = Transaction::query()
    ->whereIn('bank_account_id', $accountIds)
    ->whereBetween('date', ['2021-01-01', '2021-12-31'])
    ->whereRaw("(meta->'quality_flags')::text ILIKE '%outside_statement_period%'")
    ->orderBy('id')
    ->get(['id','bank_account_id','date','type','amount','label','normalized_label']);

echo "Candidates (2021 outside period): {$rows2021->count()}\n";

$toDelete = [];
$impactCredits = 0.0;
$impactDebits = 0.0;

foreach ($rows2021 as $tx) {
    $date = optional($tx->date)->format('Y-m-d');
    if (!$date) {
        continue;
    }

    $prevYearDate = (new DateTimeImmutable($date))->modify('-1 year')->format('Y-m-d');
    $mmdd = substr($date, 5, 5);
    $absAmount = abs((float) $tx->amount);

    $candidates = Transaction::query()
        ->where('bank_account_id', (int) $tx->bank_account_id)
        ->whereBetween('date', ['2020-01-01', '2020-12-31'])
        ->where('type', (string) $tx->type)
        ->whereRaw('ABS(amount) = ?', [$absAmount])
        ->whereRaw("to_char(date, 'MM-DD') = ?", [$mmdd])
        ->limit(8)
        ->get(['id','date','label','normalized_label']);

    if ($candidates->isEmpty()) {
        continue;
    }

    $incoming = (string) ($tx->normalized_label ?: $tx->label ?: '');
    foreach ($candidates as $cand) {
        $existing = (string) ($cand->normalized_label ?: $cand->label ?: '');
        if (!labelsVeryLikelySame($incoming, $existing)) {
            continue;
        }

        $toDelete[] = (int) $tx->id;
        if ((string) $tx->type === 'credit') {
            $impactCredits += $absAmount;
        } else {
            $impactDebits += $absAmount;
        }
        break;
    }
}

$toDelete = array_values(array_unique($toDelete));

echo "Matched duplicates to remove: ".count($toDelete)."\n";
echo "Impact credits: ".number_format($impactCredits, 2, ',', ' ')." €\n";
echo "Impact debits: ".number_format($impactDebits, 2, ',', ' ')." €\n";

if (!$dryRun && $toDelete !== []) {
    foreach (array_chunk($toDelete, 500) as $chunk) {
        Transaction::query()->whereIn('id', $chunk)->delete();
    }
    echo "Deleted: ".count($toDelete)." rows\n";
}

function labelsVeryLikelySame(string $left, string $right): bool
{
    $leftRaw = mb_strtoupper(trim($left));
    $rightRaw = mb_strtoupper(trim($right));

    if ($leftRaw === '' || $rightRaw === '') {
        return false;
    }

    if ($leftRaw === $rightRaw) {
        return true;
    }

    $leftCanonical = canonicalize($leftRaw);
    $rightCanonical = canonicalize($rightRaw);

    if ($leftCanonical === '' || $rightCanonical === '') {
        return false;
    }

    if ($leftCanonical === $rightCanonical) {
        return true;
    }

    $minLen = min(mb_strlen($leftCanonical), mb_strlen($rightCanonical));
    if ($minLen >= 18 && (str_contains($leftCanonical, $rightCanonical) || str_contains($rightCanonical, $leftCanonical))) {
        return true;
    }

    $maxLen = max(mb_strlen($leftCanonical), mb_strlen($rightCanonical), 1);
    $distance = levenshtein(mb_substr($leftCanonical, 0, 255), mb_substr($rightCanonical, 0, 255));

    return ($distance / $maxLen) <= 0.22;
}

function canonicalize(string $label): string
{
    $normalized = mb_strtoupper(trim($label));
    $normalized = preg_replace('/^\d{1,2}[\.\s]\d{2}\s+/u', '', $normalized) ?? $normalized;
    $normalized = preg_replace('/\b(?:REF|REFDO|REFBEN|EMETTEUR|EMETTEUR\/|MDT|IBAN|BIC|RIB|MOT|MOTIF|BEN|IBEN|DU)\b[^\n]*/u', ' ', $normalized) ?? $normalized;
    $normalized = preg_replace('/\b[A-Z0-9]{10,}\b/u', ' ', $normalized) ?? $normalized;
    $normalized = preg_replace('/\b\d+\b/u', ' ', $normalized) ?? $normalized;
    $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

    return trim($normalized);
}
