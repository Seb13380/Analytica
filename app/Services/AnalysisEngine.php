<?php

namespace App\Services;

use App\Models\AnalysisResult;
use App\Models\CaseFile;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalysisEngine
{
    public function analyzeCase(CaseFile $caseFile, ?Carbon $rangeStart = null, ?Carbon $rangeEnd = null): AnalysisResult
    {
        $accountIds = $caseFile->bankAccounts()->pluck('id');

        $txQuery = Transaction::query()->whereIn('bank_account_id', $accountIds);
        if ($rangeStart !== null) {
            $txQuery->whereDate('date', '>=', $rangeStart->toDateString());
        }
        if ($rangeEnd !== null) {
            $txQuery->whereDate('date', '<=', $rangeEnd->toDateString());
        }

        $transactions = $txQuery
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $scored = $this->scoreTransactions($caseFile, $transactions);

        DB::transaction(function () use ($scored, $accountIds) {
            Transaction::query()
                ->whereIn('bank_account_id', $accountIds)
                ->update([
                    'anomaly_score' => 0,
                    'anomaly_level' => 'normal',
                    'rule_flags' => json_encode([]),
                ]);

            foreach ($scored as $txId => $payload) {
                Transaction::query()->whereKey($txId)->update($payload);
            }
        });

        $summary = $this->computeCaseSummary($caseFile, $scored, $transactions);

        $caseFile->forceFill(['global_score' => $summary['global_score']])->save();

        return AnalysisResult::create([
            'case_id' => $caseFile->getKey(),
            'generated_at' => now(),
            'global_score' => $summary['global_score'],
            'total_transactions' => $summary['total_transactions'],
            'total_flagged' => $summary['total_flagged'],
            'total_flagged_amount' => $summary['total_flagged_amount'],
        ]);
    }

    /**
     * @param Collection<int,Transaction> $transactions
     * @return array<int,array{anomaly_score:int,anomaly_level:string,rule_flags:array}>
     */
    private function scoreTransactions(CaseFile $caseFile, Collection $transactions): array
    {
        $byBeneficiary = $transactions->groupBy(fn (Transaction $t) => (string) ($t->normalized_label ?? ''));

        $deathDate = $caseFile->death_date ? Carbon::parse($caseFile->death_date) : null;
        $deathWindowStart = $deathDate ? $deathDate->copy()->subMonths(6) : null;

        // Pre-compute z-score stats per bank account.
        $stats = $transactions
            ->groupBy('bank_account_id')
            ->map(function (Collection $txs) {
                $amounts = $txs->map(fn (Transaction $t) => abs((float) $t->amount))->values();
                $mean = $amounts->avg() ?? 0.0;
                $variance = $amounts->map(fn ($a) => ($a - $mean) ** 2)->avg() ?? 0.0;
                $std = sqrt($variance);

                return ['mean' => $mean, 'std' => $std];
            });

        // Monthly averages for R1 (rough MVP).
        $monthlyAvg = $transactions
            ->groupBy(fn (Transaction $t) => Carbon::parse($t->date)->format('Y-m'))
            ->map(fn (Collection $txs) => $txs->map(fn (Transaction $t) => abs((float) $t->amount))->avg() ?? 0.0);

        $out = [];

        foreach ($transactions as $tx) {
            $score = 0;
            $flags = [];

            $amountAbs = abs((float) $tx->amount);
            $monthKey = Carbon::parse($tx->date)->format('Y-m');
            $avgMonth = (float) ($monthlyAvg[$monthKey] ?? 0.0);

            // R1 – Montant élevé
            if ($avgMonth > 0 && $amountAbs > ($avgMonth * 3)) {
                $score += 20;
                $flags['R1_high_amount'] = true;
            }

            // R2 – Bénéficiaire récurrent inhabituel
            $benefKey = (string) ($tx->normalized_label ?? '');
            if ($benefKey !== '' && ($byBeneficiary[$benefKey]?->count() ?? 0) > 3) {
                $score += 15;
                $flags['R2_recurrent_beneficiary'] = true;
            }

            // R3 – Pic avant décès
            if ($deathDate && $deathWindowStart) {
                $txDate = Carbon::parse($tx->date);
                if ($txDate->betweenIncluded($deathWindowStart, $deathDate)) {
                    $score += 15;
                    $flags['R3_pre_death'] = true;
                }
            }

            // R4 – Fractionnement (3 virements similaires < 10 jours)
            if ($benefKey !== '') {
                $window = $transactions
                    ->filter(fn (Transaction $t) => (string) ($t->normalized_label ?? '') === $benefKey)
                    ->filter(function (Transaction $t) use ($tx) {
                        $delta = abs(Carbon::parse($t->date)->diffInDays(Carbon::parse($tx->date), false));
                        if ($delta > 10) {
                            return false;
                        }
                        $a = abs((float) $t->amount);
                        $b = abs((float) $tx->amount);
                        if ($b <= 0) {
                            return false;
                        }
                        return abs($a - $b) / $b <= 0.02;
                    });

                if ($window->count() >= 3) {
                    $score += 20;
                    $flags['R4_structuring'] = true;
                }
            }

            // R5 – Retrait espèces important
            $label = (string) $tx->normalized_label;
            if (str_contains($label, 'RETRAIT') || str_contains($label, 'ATM') || str_contains($label, 'DAB')) {
                $withdrawals = $transactions
                    ->filter(fn (Transaction $t) => str_contains((string) ($t->normalized_label ?? ''), 'RETRAIT')
                        || str_contains((string) ($t->normalized_label ?? ''), 'ATM')
                        || str_contains((string) ($t->normalized_label ?? ''), 'DAB'))
                    ->map(fn (Transaction $t) => abs((float) $t->amount));

                $threshold = ($withdrawals->avg() ?? 0.0) + 2 * sqrt(($withdrawals->map(fn ($a) => ($a - ($withdrawals->avg() ?? 0.0)) ** 2)->avg() ?? 0.0));

                if ($threshold > 0 && $amountAbs > $threshold) {
                    $score += 15;
                    $flags['R5_large_cash_withdrawal'] = true;
                }
            }

            // R6 – Rupture statistique (Z-score > 2)
            $s = $stats[$tx->bank_account_id] ?? ['mean' => 0.0, 'std' => 0.0];
            if (($s['std'] ?? 0.0) > 0) {
                $z = ($amountAbs - (float) $s['mean']) / (float) $s['std'];
                if ($z > 2) {
                    $score += 25;
                    $flags['R6_zscore_break'] = true;
                }
            }

            $score = min(100, $score);
            $level = $this->levelForScore($score);

            $out[$tx->getKey()] = [
                'anomaly_score' => $score,
                'anomaly_level' => $level,
                'rule_flags' => $flags,
            ];
        }

        return $out;
    }

    private function levelForScore(int $score): string
    {
        if ($score < 30) {
            return 'normal';
        }
        if ($score < 60) {
            return 'atypique';
        }
        return 'fortement_atypique';
    }

    /**
     * @param array<int,array{anomaly_score:int,anomaly_level:string,rule_flags:array}> $scored
     */
    private function computeCaseSummary(CaseFile $caseFile, array $scored, Collection $transactions): array
    {
        $scores = collect($scored)->pluck('anomaly_score');
        $flagged = $scores->filter(fn ($s) => $s >= 30);

        $totalTransactions = $transactions->count();
        $totalFlagged = $flagged->count();

        $flaggedAmount = $transactions
            ->filter(fn (Transaction $t) => (($scored[$t->getKey()]['anomaly_score'] ?? 0) >= 30))
            ->sum(fn (Transaction $t) => abs((float) $t->amount));

        $avgFlaggedScore = $totalFlagged > 0 ? (int) round($flagged->avg()) : 0;

        // Beneficiary concentration weight.
        $byBenef = $transactions
            ->filter(fn (Transaction $t) => (($scored[$t->getKey()]['anomaly_score'] ?? 0) >= 30))
            ->groupBy(fn (Transaction $t) => (string) ($t->normalized_label ?? ''))
            ->map(fn (Collection $txs) => $txs->sum(fn (Transaction $t) => abs((float) $t->amount)))
            ->sortDesc();

        $topBenefAmount = (float) ($byBenef->first() ?? 0.0);
        $concentration = $flaggedAmount > 0 ? $topBenefAmount / $flaggedAmount : 0.0;
        $benefWeight = $concentration >= 0.5 ? 20 : ($concentration >= 0.3 ? 10 : 0);

        // Pre-death weight.
        $deathDate = $caseFile->death_date ? Carbon::parse($caseFile->death_date) : null;
        $preDeathWeight = 0;
        if ($deathDate) {
            $windowStart = $deathDate->copy()->subMonths(6);
            $preDeathFlagged = $transactions
                ->filter(fn (Transaction $t) => (($scored[$t->getKey()]['anomaly_score'] ?? 0) >= 30))
                ->filter(fn (Transaction $t) => Carbon::parse($t->date)->betweenIncluded($windowStart, $deathDate))
                ->count();

            if ($totalFlagged > 0) {
                $preDeathWeight = (int) round(15 * ($preDeathFlagged / $totalFlagged));
            }
        }

        $global = (int) round(min(100, ($avgFlaggedScore * 0.7) + $benefWeight + $preDeathWeight));

        return [
            'global_score' => $global,
            'total_transactions' => $totalTransactions,
            'total_flagged' => $totalFlagged,
            'total_flagged_amount' => round($flaggedAmount, 2),
        ];
    }
}
