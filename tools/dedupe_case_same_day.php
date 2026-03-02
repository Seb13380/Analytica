<?php

require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\BankAccount;
use App\Models\Transaction;

$caseId = isset($argv[1]) ? (int) $argv[1] : 1;
$dryRun = isset($argv[2]) ? (int) $argv[2] === 1 : false;

$accountIds = BankAccount::query()->where('case_id', $caseId)->pluck('id');
if ($accountIds->isEmpty()) {
    echo "No accounts for case #{$caseId}\n";
    exit(0);
}

$rows = Transaction::query()
    ->whereIn('bank_account_id', $accountIds)
    ->orderBy('bank_account_id')
    ->orderBy('date')
    ->orderBy('type')
    ->orderByRaw('ABS(amount)')
    ->orderBy('id')
    ->get(['id', 'bank_account_id', 'date', 'type', 'amount', 'label', 'normalized_label', 'meta']);

$groups = $rows->groupBy(function ($tx) {
    return implode('|', [
        (int) $tx->bank_account_id,
        optional($tx->date)->format('Y-m-d'),
        (string) $tx->type,
        number_format(abs((float) $tx->amount), 2, '.', ''),
    ]);
});

$toDelete = [];
$merged = 0;

foreach ($groups as $group) {
    if ($group->count() <= 1) {
        continue;
    }

    $keepers = [];

    foreach ($group as $tx) {
        $matchedIdx = null;
        foreach ($keepers as $idx => $keep) {
            if (labelsVeryLikelySame((string) ($tx->normalized_label ?: $tx->label), (string) ($keep->normalized_label ?: $keep->label))) {
                $matchedIdx = $idx;
                break;
            }
        }

        if ($matchedIdx === null) {
            $keepers[] = $tx;
            continue;
        }

        $currentKeep = $keepers[$matchedIdx];
        $keepScore = qualityScore($currentKeep);
        $txScore = qualityScore($tx);

        if ($txScore > $keepScore) {
            $toDelete[] = (int) $currentKeep->id;
            $keepers[$matchedIdx] = $tx;
        } else {
            $toDelete[] = (int) $tx->id;
        }

        $merged++;
    }
}

$toDelete = array_values(array_unique($toDelete));

if (!$dryRun && $toDelete !== []) {
    foreach (array_chunk($toDelete, 500) as $chunk) {
        Transaction::query()->whereIn('id', $chunk)->delete();
    }
}

echo 'Case: '.$caseId."\n";
echo 'Dry run: '.($dryRun ? 'yes' : 'no')."\n";
echo 'Candidate groups: '.$groups->filter(fn ($g) => $g->count() > 1)->count()."\n";
echo 'Merged pairs: '.$merged."\n";
echo 'Rows to delete: '.count($toDelete)."\n";

function qualityScore(Transaction $tx): int
{
    $label = mb_strtoupper((string) ($tx->normalized_label ?: $tx->label ?: ''));
    $score = 0;

    $meta = is_array($tx->meta ?? null) ? $tx->meta : [];
    $score += (int) ($meta['confidence'] ?? 0);

    if (is_array($meta['statement_period'] ?? null)) {
        $score += 15;
    }

    if (preg_match('/\b(BNP\s+PARIBAS\s+SA|RCS|ORIAS|MONNAIE\s+DU\s+COMPTE|SERVICE\s+CLIENT)\b/u', $label) === 1) {
        $score -= 40;
    }

    if (mb_strlen($label) > 320) {
        $score -= 25;
    }

    if (mb_strlen($label) <= 140) {
        $score += 10;
    }

    return $score;
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
    if ($minLen >= 20 && (str_contains($leftCanonical, $rightCanonical) || str_contains($rightCanonical, $leftCanonical))) {
        return true;
    }

    $maxLen = max(mb_strlen($leftCanonical), mb_strlen($rightCanonical), 1);
    $distance = levenshtein(mb_substr($leftCanonical, 0, 255), mb_substr($rightCanonical, 0, 255));

    return ($distance / $maxLen) <= 0.20;
}

function canonicalize(string $label): string
{
    $normalized = mb_strtoupper(trim($label));
    $normalized = preg_replace('/\bBNP\s+PARIBAS\s+SA\b.*$/u', ' ', $normalized) ?? $normalized;
    $normalized = preg_replace('/\b(?:RCS|ORIAS|SIEGE|SI[EÈ]GE|SERVICE\s+CLIENT|MONNAIE\s+DU\s+COMPTE)\b.*$/u', ' ', $normalized) ?? $normalized;
    $normalized = preg_replace('/\b(?:REF|REFDO|REFBEN|EMETTEUR|EMETTEUR\/|MDT|IBAN|BIC|RIB|LIB|MOT|MOTIF)\b[^\n]*/u', ' ', $normalized) ?? $normalized;
    $normalized = preg_replace('/\b[A-Z0-9]{10,}\b/u', ' ', $normalized) ?? $normalized;
    $normalized = preg_replace('/\b\d+\b/u', ' ', $normalized) ?? $normalized;
    $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

    return trim($normalized);
}
