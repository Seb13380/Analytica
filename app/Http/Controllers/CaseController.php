<?php

namespace App\Http\Controllers;

use App\Exports\CaseTransactionsExport;
use App\Models\BeneficiaryAliasOverride;
use App\Models\CaseFile;
use App\Models\Statement;
use App\Models\Transaction;
use App\Services\AnalysisEngine;
use App\Services\AiAssistant;
use App\Services\EncryptedFileStorage;
use App\Services\Normalization;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class CaseController extends Controller
{
    /** Manual beneficiary overrides for the current request (normalized_label → override). */
    private \Illuminate\Support\Collection $activeOverrides;

    public function index(Request $request)
    {
        $user = $request->user();
        Gate::authorize('viewAny', CaseFile::class);

        $orgIds = $user->organizations()->pluck('organizations.id');

        $cases = CaseFile::query()
            ->where('user_id', $user->getKey())
            ->orWhereIn('organization_id', $orgIds)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('cases.index', [
            'cases' => $cases,
        ]);
    }

    public function create(Request $request)
    {
        Gate::authorize('create', CaseFile::class);

        $organizations = $request->user()->organizations()->orderBy('name')->get();

        return view('cases.create', [
            'organizations' => $organizations,
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', CaseFile::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'organization_id' => ['nullable', 'integer'],
            'deceased_name' => ['nullable', 'string', 'max:255'],
            'death_date' => ['nullable', 'date'],
            'analysis_period_start' => ['nullable', 'date'],
            'analysis_period_end' => ['nullable', 'date', 'after_or_equal:analysis_period_start'],
        ]);

        $orgId = $validated['organization_id'] ?? null;
        if ($orgId !== null) {
            $orgId = $request->user()->organizations()->whereKey($orgId)->exists() ? $orgId : null;
        }

        $case = CaseFile::create([
            'organization_id' => $orgId,
            'user_id' => $request->user()->getKey(),
            'title' => $validated['title'],
            'deceased_name' => $validated['deceased_name'] ?? null,
            'death_date' => $validated['death_date'] ?? null,
            'analysis_period_start' => $validated['analysis_period_start'] ?? null,
            'analysis_period_end' => $validated['analysis_period_end'] ?? null,
            'status' => 'draft',
            'expires_at' => now()->addMonths((int) config('analytica.case_expiration_months', 24)),
        ]);

        return redirect()->route('cases.show', $case);
    }

    public function updateDetails(Request $request, CaseFile $case)
    {
        Gate::authorize('update', $case);

        $validated = $request->validate([
            'deceased_name' => ['nullable', 'string', 'max:255'],
            'analysis_period_start' => ['nullable', 'date'],
            'analysis_period_end' => ['nullable', 'date', 'after_or_equal:analysis_period_start'],
        ]);

        $rawDeathDate = trim((string) $request->input('death_date', ''));
        $deathDate = null;

        if ($rawDeathDate !== '') {
            $parsed = null;
            foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
                try {
                    $parsed = Carbon::createFromFormat($format, $rawDeathDate);
                    break;
                } catch (\Throwable) {
                    // try next
                }
            }

            if ($parsed === null) {
                try {
                    $parsed = Carbon::parse($rawDeathDate);
                } catch (\Throwable) {
                    return redirect()
                        ->route('cases.show', $case)
                        ->withErrors(['death_date' => 'Format de date invalide. Utilise JJ/MM/AAAA (ex: 17/10/2025).'])
                        ->withInput();
                }
            }

            $deathDate = $parsed->toDateString();
        }

        $case->forceFill([
            'deceased_name' => $validated['deceased_name'] ?? null,
            'death_date' => $deathDate,
            'analysis_period_start' => $validated['analysis_period_start'] ?? null,
            'analysis_period_end' => $validated['analysis_period_end'] ?? null,
        ])->save();

        return redirect()->route('cases.show', $case)->with('status', 'Informations dossier mises à jour.');
    }

    public function show(Request $request, CaseFile $case)
    {
        Gate::authorize('view', $case);

        $case->load(['bankAccounts.statements', 'reports']);

        $filters = $this->buildTransactionFilters($request);

        $exceptionalThresholdRaw = (string) $request->query('exceptional_threshold', '20000');
        $exceptionalThreshold = is_numeric($exceptionalThresholdRaw)
            ? max(500.0, (float) $exceptionalThresholdRaw)
            : 20000.0;

        $accountProfileById = $case->bankAccounts->mapWithKeys(function ($account) {
            $texts = $account->statements
                ->pluck('extracted_text')
                ->filter(fn ($t) => is_string($t) && trim($t) !== '')
                ->values()
                ->all();

            $holders = collect();
            if (!empty($account->account_holder)) {
                $holders->push(Normalization::cleanLabel((string) $account->account_holder));
            }
            foreach ($texts as $text) {
                foreach ($this->extractHolderMentionsFromText((string) $text) as $holder) {
                    $holders->push($holder);
                }
            }

            $isJoint = $this->detectJointHolderProfile((string) ($account->account_holder ?? ''), $texts, $holders->unique()->values()->all());

            return [$account->getKey() => $isJoint ? 'joint' : 'personal'];
        });

        $selectedAccounts = $case->bankAccounts;

        $accountDisplayById = $case->bankAccounts->mapWithKeys(function ($account) use ($accountProfileById) {
            $profile = (string) ($accountProfileById[$account->getKey()] ?? 'personal');
            $profileLabel = $profile === 'joint' ? 'Compte commun' : 'Compte personnel';

            $holder = trim((string) ($account->account_holder ?? ''));
            $holderLabel = $holder;
            if ($holderLabel === '') {
                $holderLabel = $profile === 'joint' ? 'M. / Mme' : 'M. ou Mme';
            }

            $accountNumber = trim((string) ($account->iban_masked ?? ''));
            $bank = trim((string) ($account->bank_name ?? 'Compte'));

            $parts = [$bank, $profileLabel, $holderLabel];
            if ($accountNumber !== '') {
                $parts[] = $accountNumber;
            }

            return [$account->getKey() => implode(' · ', array_filter($parts, fn ($part) => is_string($part) && trim($part) !== ''))];
        });

        if ($filters['bank_name'] !== '') {
            $selectedAccounts = $selectedAccounts->where('bank_name', $filters['bank_name']);
        }

        if ($filters['account_profile'] === 'joint' || $filters['account_profile'] === 'personal') {
            $filtered = $selectedAccounts->filter(function ($account) use ($accountProfileById, $filters) {
                return ($accountProfileById[$account->getKey()] ?? 'personal') === $filters['account_profile'];
            })->values();
            if ($filtered->isNotEmpty()) {
                $selectedAccounts = $filtered;
            } else {
                $filters['account_profile'] = '';
            }
        }

        if ($filters['bank_account_id'] !== '' && ctype_digit($filters['bank_account_id'])) {
            $selectedAccounts = $selectedAccounts->where('id', (int) $filters['bank_account_id']);
        }

        if ($filters['date_from'] === '' && !is_null($case->analysis_period_start)) {
            $filters['date_from'] = $case->analysis_period_start->toDateString();
        }
        if ($filters['date_to'] === '' && !is_null($case->analysis_period_end)) {
            $filters['date_to'] = $case->analysis_period_end->toDateString();
        }

        if ($filters['date_from'] === '' && $filters['date_to'] === '') {
            $detectedPeriods = $selectedAccounts
                ->flatMap(function ($account) {
                    return $account->statements->map(function ($statement) {
                        $text = is_string($statement->extracted_text ?? null) ? (string) $statement->extracted_text : '';

                        return $text !== '' ? $this->extractStatementPeriodFromText($text) : null;
                    });
                })
                ->filter(fn ($period) => is_array($period) && !empty($period['start']) && !empty($period['end']))
                ->values();

            if ($detectedPeriods->isNotEmpty()) {
                $filters['date_from'] = (string) $detectedPeriods->min('start');
                $filters['date_to'] = (string) $detectedPeriods->max('end');
            }
        }

        $accountIds = $selectedAccounts->pluck('id');
        $latestAnalysisResult = $case->analysisResults()->orderByDesc('generated_at')->orderByDesc('id')->first();

        // Load manual beneficiary overrides for this case (used by resolveBeneficiaryIdentity).
        $this->activeOverrides = BeneficiaryAliasOverride::where('case_id', $case->getKey())
            ->get()
            ->keyBy('normalized_label');

        $totalTransactions = Transaction::query()->whereIn('bank_account_id', $accountIds)->count();
        $flagged = Transaction::query()
            ->whereIn('bank_account_id', $accountIds)
            ->where('anomaly_score', '>=', 30)
            ->get();

        $totalFlagged = $flagged->count();
        $totalFlaggedAmount = (float) $flagged->sum(fn ($t) => abs((float) $t->amount));

        $topBeneficiaries = $flagged
            ->groupBy(fn ($t) => (string) ($t->normalized_label ?? ''))
            ->map(fn ($txs) => $txs->sum(fn ($t) => abs((float) $t->amount)))
            ->sortDesc()
            ->take(5);

        $timeline = Transaction::query()
            ->selectRaw("to_char(date, 'YYYY-MM') as ym, count(*) as cnt")
            ->whereIn('bank_account_id', $accountIds)
            ->where('anomaly_score', '>=', 30)
            ->groupBy('ym')
            ->orderBy('ym')
            ->get()
            ->mapWithKeys(fn ($r) => [$r->ym => (int) $r->cnt]);

        $displayTotalFlagged = $totalFlagged;
        $displayTotalFlaggedAmount = round($totalFlaggedAmount, 2);

        if ($displayTotalFlagged === 0 && $latestAnalysisResult) {
            $displayTotalFlagged = (int) ($latestAnalysisResult->total_flagged ?? 0);
            $displayTotalFlaggedAmount = round((float) ($latestAnalysisResult->total_flagged_amount ?? 0), 2);
        }

        $stats = [
            'global_score' => $case->global_score,
            'total_accounts' => $selectedAccounts->count(),
            'total_transactions' => $totalTransactions,
            'total_flagged' => $displayTotalFlagged,
            'total_flagged_amount' => $displayTotalFlaggedAmount,
            'top_beneficiaries' => $topBeneficiaries,
            'timeline' => $timeline,
        ];

        $analyticsBase = Transaction::query()->whereIn('bank_account_id', $accountIds);
        if ($filters['date_from'] !== '') {
            $analyticsBase->whereDate('date', '>=', $filters['date_from']);
        }
        if ($filters['date_to'] !== '') {
            $analyticsBase->whereDate('date', '<=', $filters['date_to']);
        }

        $txBase = clone $analyticsBase;
        $this->applyTransactionFilters($txBase, $filters);

        $totalDebit = (float) (clone $txBase)->where('type', 'debit')->sum(DB::raw('abs(amount)'));
        $totalCredit = (float) (clone $txBase)->where('type', 'credit')->sum(DB::raw('abs(amount)'));
        $net = $totalCredit - $totalDebit;

        $transactions = (clone $txBase)
            ->orderBy('date')
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString();

        $transactions->setCollection(
            $transactions->getCollection()->map(function ($tx) {
                $display = $this->buildDisplayLabel((string) ($tx->motif ?? $tx->label ?? ''), 120);
                $tx->display_label = $display['short'];
                $tx->display_label_full = $display['full'];
                $tx->display_label_truncated = $display['truncated'];

                return $tx;
            })
        );

        $reports = $case->reports->sortByDesc('generated_at')->take(20);
        $latestPdfReport = $reports->first(fn ($report) => str_contains((string) ($report->mime_type ?? ''), 'pdf'));
        $persistedAi = [
            'prompt' => (string) ($case->ai_last_prompt ?? ''),
            'result' => is_array($case->ai_last_result ?? null) ? $case->ai_last_result : null,
            'error' => (string) ($case->ai_last_error ?? ''),
            'ran_at' => optional($case->ai_last_ran_at)->toIso8601String(),
        ];
        $sessionAi = (array) $request->session()->get('cases.'.$case->getKey().'.last_ai', []);
        $lastAi = array_merge($persistedAi, $sessionAi);

        $allTx = (clone $analyticsBase)
            ->orderBy('date')
            ->get(['id', 'bank_account_id', 'date', 'amount', 'type', 'kind', 'origin', 'destination', 'motif', 'label', 'normalized_label'])
            ->unique('id');

        $exceptionalTransactions = $allTx
            ->filter(fn ($t) => abs((float) $t->amount) >= $exceptionalThreshold)
            ->sortBy(function ($t) {
                $date = optional($t->date)->format('Y-m-d') ?? '9999-12-31';
                $id = str_pad((string) ($t->id ?? 0), 10, '0', STR_PAD_LEFT);

                return $date.'#'.$id;
            })
            ->map(function ($t) {
                $display = $this->buildDisplayLabel((string) ($t->label ?? ''), 120);
                $t->display_label = $display['short'];
                $t->display_label_full = $display['full'];
                $t->display_label_truncated = $display['truncated'];

                return $t;
            })
            ->values();

        $importHighValueThreshold = (float) config('analytica.import.high_value_threshold', 20000);
        $statementDiagnostics = $case->bankAccounts
            ->flatMap(function ($account) use ($importHighValueThreshold) {
                return $account->statements->map(function ($statement) use ($account, $importHighValueThreshold) {
                    $text = is_string($statement->extracted_text) ? $statement->extracted_text : '';
                    $ocrHighValues = $text !== ''
                        ? $this->extractHighValueAmountsFromText($text, $importHighValueThreshold)
                        : [];

                    $period = $text !== '' ? $this->extractStatementPeriodFromText($text) : null;
                    $importedHighValueCount = null;

                    if (is_array($period) && !empty($period['start']) && !empty($period['end'])) {
                        $importedHighValueCount = Transaction::query()
                            ->where('bank_account_id', $account->getKey())
                            ->whereBetween('date', [$period['start'], $period['end']])
                            ->whereRaw('ABS(amount) >= ?', [$importHighValueThreshold])
                            ->count();
                    }

                    $status = (string) ($statement->import_status ?? '—');
                    $suspectMissing = $status === 'completed'
                        && count($ocrHighValues) > 0
                        && ((int) ($importedHighValueCount ?? 0)) === 0;

                    return [
                        'statement' => $statement,
                        'bank_name' => (string) ($account->bank_name ?? 'Compte'),
                        'ocr_high_values' => $ocrHighValues,
                        'ocr_high_values_count' => count($ocrHighValues),
                        'imported_high_values_count' => $importedHighValueCount,
                        'period' => $period,
                        'suspect_missing' => $suspectMissing,
                    ];
                });
            })
            ->sortByDesc(fn ($row) => optional($row['statement']->created_at ?? null)->timestamp ?? 0)
            ->take(12)
            ->values();

        $overallFrom = $allTx->min('date');
        $overallTo = $allTx->max('date');

        $accountChangeEvents = collect();

        $accountInsights = $selectedAccounts->values()->map(function ($account) use ($allTx, &$accountChangeEvents) {
            $txForAccount = $allTx->where('bank_account_id', $account->getKey());
            $texts = $account->statements
                ->pluck('extracted_text')
                ->filter(fn ($t) => is_string($t) && trim($t) !== '')
                ->values();

            $statementProfiles = $account->statements
                ->sortBy(fn ($s) => optional($s->imported_at ?? $s->created_at)?->timestamp ?? 0)
                ->values()
                ->map(function ($statement) {
                    $text = is_string($statement->extracted_text ?? null) ? (string) $statement->extracted_text : '';

                    $ids = collect($this->extractAccountIdentifiersFromText($text))
                        ->map(fn ($id) => $this->normalizeAccountIdentifier($id))
                        ->filter(fn ($id) => $id !== '')
                        ->unique()
                        ->sort()
                        ->values();

                    $holders = collect($this->extractHolderMentionsFromText($text))
                        ->map(fn ($h) => Normalization::cleanLabel((string) $h))
                        ->filter(fn ($h) => $h !== '')
                        ->unique()
                        ->sort()
                        ->values();

                    $joint = $this->detectJointHolderProfile('', [$text], $holders->all());

                    return [
                        'date' => optional($statement->imported_at ?? $statement->created_at)?->format('Y-m-d'),
                        'statement_id' => $statement->getKey(),
                        'ids' => $ids,
                        'holders' => $holders,
                        'joint' => $joint,
                    ];
                });

            $identifiers = collect();

            if (!empty($account->iban_masked)) {
                $identifiers->push($this->normalizeAccountIdentifier((string) $account->iban_masked));
            }

            foreach ($texts as $text) {
                foreach ($this->extractAccountIdentifiersFromText((string) $text) as $identifier) {
                    $identifiers->push($this->normalizeAccountIdentifier($identifier));
                }
            }

            $identifiers = $identifiers
                ->filter(fn ($id) => $id !== '')
                ->unique()
                ->values();

            $holderMentions = collect();

            if (!empty($account->account_holder)) {
                $holderMentions->push(Normalization::cleanLabel((string) $account->account_holder));
            }

            foreach ($texts as $text) {
                foreach ($this->extractHolderMentionsFromText((string) $text) as $holder) {
                    $holderMentions->push($holder);
                }
            }

            $holderMentions = $holderMentions
                ->filter(fn ($name) => $name !== '')
                ->unique()
                ->values();

            $jointDetected = $this->detectJointHolderProfile((string) ($account->account_holder ?? ''), $texts->all(), $holderMentions->all());

            $prevIds = null;
            $prevHolders = null;
            $prevJoint = null;

            foreach ($statementProfiles as $profile) {
                $currentIds = $profile['ids']->implode('|');
                $currentHolders = $profile['holders']->implode('|');
                $currentJoint = (bool) $profile['joint'];

                if (!is_null($prevIds) && $prevIds !== $currentIds) {
                    $accountChangeEvents->push([
                        'date' => $profile['date'] ?? null,
                        'bank_name' => (string) $account->bank_name,
                        'type' => 'identifier_change',
                        'message' => 'Changement de numéro de compte détecté',
                    ]);
                }

                if (!is_null($prevHolders) && $prevHolders !== $currentHolders) {
                    $accountChangeEvents->push([
                        'date' => $profile['date'] ?? null,
                        'bank_name' => (string) $account->bank_name,
                        'type' => 'holder_change',
                        'message' => 'Variation de mention titulaire (M / MME / nom)',
                    ]);
                }

                if (!is_null($prevJoint) && $prevJoint !== $currentJoint) {
                    $accountChangeEvents->push([
                        'date' => $profile['date'] ?? null,
                        'bank_name' => (string) $account->bank_name,
                        'type' => 'joint_profile_change',
                        'message' => 'Variation profil compte commun/personnel',
                    ]);
                }

                $prevIds = $currentIds;
                $prevHolders = $currentHolders;
                $prevJoint = $currentJoint;
            }

            $firstTxDate = $txForAccount->min('date');
            $lastTxDate = $txForAccount->max('date');

            return [
                'bank_name' => (string) $account->bank_name,
                'bank_account_id' => $account->getKey(),
                'transactions_count' => $txForAccount->count(),
                'period_start' => $firstTxDate ? Carbon::parse($firstTxDate)->format('Y-m-d') : null,
                'period_end' => $lastTxDate ? Carbon::parse($lastTxDate)->format('Y-m-d') : null,
                'identifiers' => $identifiers->map(fn ($id) => $this->formatAccountIdentifier($id))->all(),
                'has_identifier_change' => $identifiers->count() > 1,
                'holders' => $holderMentions->all(),
                'is_joint' => $jointDetected,
            ];
        })->values();

        $allTxWithDate = $allTx->filter(fn ($t) => !is_null($t->date));

        $monthlyTotalsMap = $allTxWithDate
            ->groupBy(fn ($t) => optional($t->date)->format('Y-m'))
            ->map(function ($txs, $month) {
                $credits = (float) $txs->where('type', 'credit')->sum(fn ($t) => abs((float) $t->amount));
                $debits = (float) $txs->where('type', 'debit')->sum(fn ($t) => abs((float) $t->amount));

                return [
                    'month' => (string) $month,
                    'credits' => round($credits, 2),
                    'debits' => round($debits, 2),
                    'net' => round($credits - $debits, 2),
                    'count' => $txs->count(),
                ];
            });

        $monthlyTotals = collect();
        if ($allTxWithDate->isNotEmpty()) {
            $dataStart = Carbon::parse($allTxWithDate->min('date'))->startOfMonth();
            $dataEnd   = Carbon::parse($allTxWithDate->max('date'))->startOfMonth();
            $caseStart = $case->analysis_period_start ? $case->analysis_period_start->copy()->startOfMonth() : null;
            $caseEnd   = $case->analysis_period_end   ? $case->analysis_period_end->copy()->startOfMonth()   : null;
            $periodStart = ($caseStart && $caseStart->lt($dataStart)) ? $caseStart : $dataStart;
            $periodEnd   = ($caseEnd   && $caseEnd->gt($dataEnd))     ? $caseEnd   : $dataEnd;

            for ($cursor = $periodStart->copy(); $cursor->lte($periodEnd); $cursor->addMonth()) {
                $monthKey = $cursor->format('Y-m');
                $monthlyTotals->push($monthlyTotalsMap->get($monthKey, [
                    'month' => $monthKey,
                    'credits' => 0.0,
                    'debits' => 0.0,
                    'net' => 0.0,
                    'count' => 0,
                ]));
            }
        }

        $yearlyTotals = collect();
        if ($allTxWithDate->isNotEmpty()) {
            $yearlyTotalsMap = $allTxWithDate
                ->groupBy(fn ($t) => optional($t->date)->format('Y'))
                ->map(function ($txs, $year) {
                    $credits = (float) $txs->where('type', 'credit')->sum(fn ($t) => abs((float) $t->amount));
                    $debits = (float) $txs->where('type', 'debit')->sum(fn ($t) => abs((float) $t->amount));

                    return [
                        'year' => (string) $year,
                        'credits' => round($credits, 2),
                        'debits' => round($debits, 2),
                        'net' => round($credits - $debits, 2),
                        'count' => $txs->count(),
                    ];
                });

            $startYear = (int) Carbon::parse($allTxWithDate->min('date'))->format('Y');
            $endYear = (int) Carbon::parse($allTxWithDate->max('date'))->format('Y');

            for ($year = $startYear; $year <= $endYear; $year++) {
                $yearKey = (string) $year;
                $yearlyTotals->push($yearlyTotalsMap->get($yearKey, [
                    'year' => $yearKey,
                    'credits' => 0.0,
                    'debits' => 0.0,
                    'net' => 0.0,
                    'count' => 0,
                ]));
            }
        }

        $monthlyMax = (float) $monthlyTotals->max(function ($m) {
            return max((float) ($m['credits'] ?? 0), (float) ($m['debits'] ?? 0));
        });

        // Statistiques analytiques : moyenne, écart-type, anomalies (z > 2), moyenne mobile 12 mois
        $creditValues = $monthlyTotals->pluck('credits')->map(fn ($v) => (float) $v);
        $debitValues  = $monthlyTotals->pluck('debits')->map(fn ($v) => (float) $v);
        $avgCredit = $creditValues->count() > 0 ? (float) $creditValues->avg() : 0.0;
        $avgDebit  = $debitValues->count() > 0  ? (float) $debitValues->avg()  : 0.0;
        $stdCredit = $creditValues->count() > 1
            ? sqrt((float) $creditValues->map(fn ($v) => pow($v - $avgCredit, 2))->avg())
            : 0.0;
        $stdDebit  = $debitValues->count() > 1
            ? sqrt((float) $debitValues->map(fn ($v) => pow($v - $avgDebit, 2))->avg())
            : 0.0;
        $totalsArrForMa = $monthlyTotals->values()->toArray();
        $monthlyTotals = $monthlyTotals->values()->map(function ($m, $i) use ($totalsArrForMa, $avgCredit, $avgDebit, $stdCredit, $stdDebit) {
            $windowStart = max(0, $i - 11);
            $window = array_slice($totalsArrForMa, $windowStart, $i - $windowStart + 1);
            $ma12c  = count($window) > 0 ? array_sum(array_column($window, 'credits')) / count($window) : 0.0;
            $ma12d  = count($window) > 0 ? array_sum(array_column($window, 'debits'))  / count($window) : 0.0;
            $creditZ = $stdCredit > 0 ? (((float) $m['credits']) - $avgCredit) / $stdCredit : 0.0;
            $debitZ  = $stdDebit  > 0 ? (((float) $m['debits'])  - $avgDebit)  / $stdDebit  : 0.0;
            return array_merge($m, [
                'credit_anomaly' => $creditZ > 2.0,
                'debit_anomaly'  => $debitZ  > 2.0,
                'credit_z'       => round($creditZ, 2),
                'debit_z'        => round($debitZ,  2),
                'ma12_credits'   => round($ma12c,  2),
                'ma12_debits'    => round($ma12d,   2),
            ]);
        });

        $deathDate = $case->death_date ? Carbon::parse($case->death_date) : null;
        $sensitiveStats = null;

        if ($deathDate) {
            $sensitiveStart = $deathDate->copy()->subMonths(3)->startOfDay();
            $sensitiveEnd = $deathDate->copy()->subDay()->endOfDay();

            $baselineStart = $deathDate->copy()->subMonths(12)->startOfDay();
            $baselineEnd = $deathDate->copy()->subMonths(3)->subDay()->endOfDay();

            $sensitive = $allTx->filter(fn ($t) => $t->date && $t->date->between($sensitiveStart, $sensitiveEnd));
            $baseline = $allTx->filter(fn ($t) => $t->date && $t->date->between($baselineStart, $baselineEnd));

            $sensitiveDebit = (float) $sensitive->where('type', 'debit')->sum(fn ($t) => abs((float) $t->amount));
            $baselineDebit = (float) $baseline->where('type', 'debit')->sum(fn ($t) => abs((float) $t->amount));

            $sensitiveMonthly = round($sensitiveDebit / 3, 2);
            $baselineMonthly = round($baselineDebit / 9, 2);

            $changePct = $baselineMonthly > 0
                ? round((($sensitiveMonthly - $baselineMonthly) / $baselineMonthly) * 100, 1)
                : null;

            $sensitiveStats = [
                'death_date' => $deathDate->toDateString(),
                'window_label' => $sensitiveStart->format('Y-m-d').' → '.$sensitiveEnd->format('Y-m-d'),
                'baseline_label' => $baselineStart->format('Y-m-d').' → '.$baselineEnd->format('Y-m-d'),
                'sensitive_count' => $sensitive->count(),
                'baseline_count' => $baseline->count(),
                'sensitive_monthly_debit' => $sensitiveMonthly,
                'baseline_monthly_debit' => $baselineMonthly,
                'change_pct' => $changePct,
                'severity' => $changePct === null ? 'neutral' : ($changePct >= 30 ? 'high' : ($changePct >= 10 ? 'medium' : 'low')),
            ];
        }

        $debitMedian = (float) ($allTx
            ->where('type', 'debit')
            ->map(fn ($t) => abs((float) $t->amount))
            ->median() ?? 0.0);
        $spikeThreshold = max(500.0, $debitMedian * 2.5);
        $topSpikes = $allTx
            ->filter(fn ($t) => abs((float) $t->amount) >= $spikeThreshold)
            ->sortByDesc(fn ($t) => abs((float) $t->amount))
            ->take(12)
            ->map(function ($t) {
                $display = $this->buildDisplayLabel((string) ($t->label ?? ''), 120);
                $t->display_label = $display['short'];
                $t->display_label_full = $display['full'];
                $t->display_label_truncated = $display['truncated'];

                return $t;
            })
            ->values();

        $regularSeries = $this->buildRegularSeries($allTxWithDate);
        $regularInflows = $regularSeries
            ->where('type', 'credit')
            ->values()
            ->take(20);

        $regularOutflows = $regularSeries
            ->where('type', 'debit')
            ->values()
            ->take(20);

        $regularOutliers = $regularSeries
            ->flatMap(function ($series) {
                $outliers = collect((array) ($series['outliers'] ?? []));

                return $outliers->map(function ($row) use ($series) {
                    return [
                        'series_label' => (string) ($series['counterparty'] ?? '—'),
                        'series_kind_label' => (string) ($series['kind_label'] ?? '—'),
                        'series_income_category' => $series['income_category'] ?? null,
                        'series_type' => (string) ($series['type'] ?? 'debit'),
                        'date' => (string) ($row['date'] ?? ''),
                        'amount' => (float) ($row['amount'] ?? 0),
                        'label' => (string) ($row['label'] ?? ''),
                    ];
                });
            })
            ->sortByDesc('amount')
            ->values()
            ->take(30);

        $cashWithdrawals = $allTxWithDate
            ->filter(fn ($t) => (string) ($t->kind ?? '') === 'cash_withdrawal' && (string) ($t->type ?? '') === 'debit');

        $cashMonthlyTotals = $monthlyTotals
            ->map(function ($monthRow) use ($cashWithdrawals) {
                $month = (string) ($monthRow['month'] ?? '');
                $rows = $cashWithdrawals->filter(fn ($t) => optional($t->date)->format('Y-m') === $month);

                $total = (float) $rows->sum(fn ($t) => abs((float) $t->amount));
                $max = (float) $rows->max(fn ($t) => abs((float) $t->amount));

                return [
                    'month' => $month,
                    'total' => round($total, 2),
                    'count' => $rows->count(),
                    'max' => round($max, 2),
                ];
            })
            ->values();

        $cashMonthlyAverage = (float) ($cashMonthlyTotals->avg('total') ?? 0);
        $cashMonthlyStd = $cashMonthlyTotals->count() > 1
            ? sqrt((float) $cashMonthlyTotals->map(fn ($row) => pow(((float) ($row['total'] ?? 0)) - $cashMonthlyAverage, 2))->avg())
            : 0.0;
        $cashPeakThreshold = $cashMonthlyAverage + $cashMonthlyStd;
        $cashMonthlyTotals = $cashMonthlyTotals
            ->map(function ($row) use ($cashPeakThreshold) {
                $total = (float) ($row['total'] ?? 0);

                return array_merge($row, [
                    'is_peak' => $total > 0 && $total >= $cashPeakThreshold,
                ]);
            })
            ->values();
        $cashPeakMonth = $cashMonthlyTotals->sortByDesc('total')->first();
        $cashTopWithdrawals = $cashWithdrawals
            ->sortByDesc(fn ($t) => abs((float) $t->amount))
            ->take(12)
            ->map(function ($t) {
                $display = $this->buildDisplayLabel((string) ($t->label ?? ''), 120);
                $t->display_label = $display['short'];
                $t->display_label_full = $display['full'];
                $t->display_label_truncated = $display['truncated'];

                return $t;
            })
            ->values();

        $trackedKinds = ['transfer', 'cheque', 'cash_withdrawal', 'card'];
        $kindLabels = [
            'transfer' => 'Virement',
            'cheque' => 'Chèque',
            'cash_withdrawal' => 'Retrait espèces',
            'card' => 'Carte bancaire',
        ];

        $kindMonthlyBreakdown = $monthlyTotals->map(function ($monthlyRow) use ($allTxWithDate, $trackedKinds, $kindLabels) {
            $monthKey = (string) ($monthlyRow['month'] ?? '');
            $monthTransactions = $allTxWithDate->filter(fn ($t) => optional($t->date)->format('Y-m') === $monthKey);

            $kinds = collect($trackedKinds)->mapWithKeys(function ($kind) use ($monthTransactions, $kindLabels) {
                $kindTransactions = $monthTransactions->filter(function ($t) use ($kind) {
                    $txKind = (string) ($t->kind ?? '');
                    $txKind = in_array($txKind, ['transfer', 'cheque', 'cash_withdrawal', 'card'], true)
                        ? $txKind
                        : 'card';

                    return $txKind === $kind;
                });
                $credits = (float) $kindTransactions->where('type', 'credit')->sum(fn ($t) => abs((float) $t->amount));
                $debits = (float) $kindTransactions->where('type', 'debit')->sum(fn ($t) => abs((float) $t->amount));

                return [$kind => [
                    'label' => $kindLabels[$kind] ?? $kind,
                    'credit' => round($credits, 2),
                    'debit' => round($debits, 2),
                    'count' => $kindTransactions->count(),
                ]];
            });

            return [
                'month' => $monthKey,
                'kinds' => $kinds->all(),
            ];
        })->values();

        $kindPeaks = collect($trackedKinds)->map(function ($kind) use ($allTxWithDate, $kindMonthlyBreakdown, $kindLabels) {
            $kindTransactions = $allTxWithDate->filter(function ($t) use ($kind) {
                $txKind = (string) ($t->kind ?? '');
                $txKind = in_array($txKind, ['transfer', 'cheque', 'cash_withdrawal', 'card'], true)
                    ? $txKind
                    : 'card';

                return $txKind === $kind;
            });
            $totalCredit = (float) $kindTransactions->where('type', 'credit')->sum(fn ($t) => abs((float) $t->amount));
            $totalDebit = (float) $kindTransactions->where('type', 'debit')->sum(fn ($t) => abs((float) $t->amount));
            $monthlyPeak = $kindMonthlyBreakdown
                ->map(function ($monthRow) use ($kind) {
                    $kindRow = (array) (($monthRow['kinds'] ?? [])[$kind] ?? []);
                    $credit = (float) ($kindRow['credit'] ?? 0);
                    $debit = (float) ($kindRow['debit'] ?? 0);

                    return [
                        'month' => (string) ($monthRow['month'] ?? ''),
                        'amount' => max($credit, $debit),
                        'type' => $credit >= $debit ? 'credit' : 'debit',
                    ];
                })
                ->sortByDesc('amount')
                ->first();

            return [
                'kind' => $kind,
                'label' => $kindLabels[$kind] ?? $kind,
                'count' => $kindTransactions->count(),
                'total_credit' => round($totalCredit, 2),
                'total_debit' => round($totalDebit, 2),
                'monthly_peak' => [
                    'month' => (string) ($monthlyPeak['month'] ?? '—'),
                    'amount' => round((float) ($monthlyPeak['amount'] ?? 0), 2),
                    'type' => (string) ($monthlyPeak['type'] ?? 'debit'),
                ],
            ];
        })->values();

        // Build disambiguation groups: all debit transactions mentioning a known name,
        // grouped by normalized_label, used in the manual correction UI.
        $disambiguationGroups = $allTxWithDate
            ->where('type', 'debit')
            ->filter(function ($t) {
                $raw = trim((string) ($t->destination ?: $t->origin ?: $t->normalized_label ?: $t->label));
                if ($raw === '') {
                    return false;
                }
                $n = Normalization::normalizeLabel($raw);
                return str_contains($n, 'GIORDANO') || str_contains($n, 'NOVAK');
            })
            ->groupBy(function ($t) {
                $raw = trim((string) ($t->destination ?: $t->origin ?: $t->normalized_label ?: $t->label));
                return Normalization::normalizeLabel($raw);
            })
            ->map(function ($txs, $normalizedLabel) {
                $first = $txs->first();
                $identity = $this->resolveBeneficiaryIdentity($first);
                $raw = trim((string) ($first->destination ?: $first->origin ?: $first->normalized_label ?: $first->label));
                // If the user has saved a manual override for this label, it is no longer ambiguous
                // regardless of which identity key was chosen — the user made a deliberate decision.
                $hasManualOverride = isset($this->activeOverrides) && $this->activeOverrides->has((string) $normalizedLabel);
                $autoAmbiguous = in_array($identity['key'] ?? '', ['INCONNU', 'PERSONNE_GIORDANO_NOVAK'], true)
                    || str_starts_with((string) ($identity['key'] ?? ''), 'BEN_')
                    || str_starts_with((string) ($identity['key'] ?? ''), 'RAW_');
                return [
                    'normalized_label' => (string) $normalizedLabel,
                    'raw_label'        => $raw,
                    'identity_key'     => (string) ($identity['key']   ?? 'INCONNU'),
                    'identity_label'   => (string) ($identity['label'] ?? 'Inconnu'),
                    'has_override'     => $hasManualOverride,
                    'is_ambiguous'     => !$hasManualOverride && $autoAmbiguous,
                    'count'            => $txs->count(),
                    'total'            => round((float) $txs->sum(fn ($t) => abs((float) $t->amount)), 2),
                    'last_date'        => optional($txs->sortByDesc('date')->first()?->date)->format('Y-m-d'),
                ];
            })
            ->sortByDesc(fn ($g) => ((int) ($g['is_ambiguous'] ?? 0)) * 1000000 + (int) ($g['count'] ?? 0))
            ->values();

        // Labels that indicate an internal transfer between own accounts — excluded from debit concentration.
        $internalTransferPatterns = [
            'VOTRE CPTE', 'VOS COMPTES', 'VIREMENT INTERNE', 'VIR INTERNE',
            'VIREMENTS INTERNES', 'VIRE DU COMPTE', 'DESDE SU CUENTA', 'DE VOTRE CPTE',
            // Transfers between accounts of the same holder ("CPTE A CPTE" = compte à compte)
            'CPTE A CPTE',
        ];

        $outgoingTransactions = $allTxWithDate
            ->where('type', 'debit')
            ->filter(function ($t) use ($trackedKinds, $internalTransferPatterns) {
                $txKind = (string) ($t->kind ?? '');
                $txKind = in_array($txKind, $trackedKinds, true) ? $txKind : 'card';

                if (!in_array($txKind, $trackedKinds, true)) {
                    return false;
                }

                // Exclude internal transfers (movements between own accounts)
                $raw = trim((string) ($t->destination ?: $t->origin ?: $t->normalized_label ?: $t->label));
                if ($this->isInternalTransferLabel($raw)) {
                    return false;
                }

                $rawUp = mb_strtoupper($raw);
                foreach ($internalTransferPatterns as $pattern) {
                    if (str_contains($rawUp, $pattern)) {
                        return false;
                    }
                }

                return true;
            });

        $totalOutgoing = (float) $outgoingTransactions->sum(fn ($t) => abs((float) $t->amount));
        $beneficiaryConcentrationRaw = $outgoingTransactions
            ->groupBy(function ($t) {
                $identity = $this->resolveBeneficiaryIdentity($t);

                return (string) ($identity['key'] ?? 'INCONNU');
            })
            ->map(function ($txs) use ($totalOutgoing) {
                $amount = (float) $txs->sum(fn ($t) => abs((float) $t->amount));
                $first = $txs->first();
                $identity = $this->resolveBeneficiaryIdentity($first);
                $identityKey = (string) ($identity['key'] ?? 'INCONNU');

                $aliases = $txs
                    ->map(function ($t) {
                        $raw = trim((string) ($t->destination ?: $t->origin ?: $t->normalized_label ?: $t->label));

                        return Normalization::cleanLabel($raw);
                    })
                    ->filter(fn ($v) => $v !== '')
                    ->unique()
                    ->take(5)
                    ->values()
                    ->all();

                $detailsRaw = $txs
                    ->sortByDesc(fn ($t) => optional($t->date)->format('Y-m-d').'#'.str_pad((string) ($t->id ?? 0), 10, '0', STR_PAD_LEFT))
                    ->map(function ($t) {
                        $display = $this->buildDisplayLabel((string) ($t->label ?? ''), 120);

                        return [
                            'id' => (int) ($t->id ?? 0),
                            'date' => optional($t->date)->format('Y-m-d'),
                            'type' => (string) ($t->type ?? ''),
                            'amount' => round(abs((float) ($t->amount ?? 0)), 2),
                            'label' => $display['short'],
                            'label_full' => $display['full'],
                            'label_truncated' => $display['truncated'],
                        ];
                    })
                    ->values();

                $details = $detailsRaw
                    ->groupBy(function (array $row) {
                        $date = (string) ($row['date'] ?? '');
                        $amount = number_format((float) ($row['amount'] ?? 0), 2, '.', '');
                        $type = (string) ($row['type'] ?? '');
                        $label = mb_strtoupper((string) ($row['label_full'] ?? $row['label'] ?? ''));

                        return $date.'|'.$amount.'|'.$type.'|'.$label;
                    })
                    ->map(function ($rows) {
                        $first = $rows->first();
                        if (!is_array($first)) {
                            return null;
                        }

                        $first['duplicate_count'] = $rows->count();

                        return $first;
                    })
                    ->filter(fn ($row) => is_array($row))
                    ->values()
                    ->all();

                $beneficiaryLabel = (string) ($identity['label'] ?? 'INCONNU');
                $sampleText = mb_strtoupper($beneficiaryLabel.' '.implode(' ', $aliases));
                $isRecurringNoise =
                    $txs->count() >= 200
                    && preg_match('/\b(FACTURE|CARTE|COTISATION|COMMISSIONS?|PRLV|PRELEVEMENT)\b/u', $sampleText) === 1;

                $filterQuery = trim((string) (($aliases[0] ?? '') ?: $beneficiaryLabel));

                return [
                    'key' => $identityKey,
                    'beneficiary' => (string) ($identity['label'] ?? 'INCONNU'),
                    'amount' => round($amount, 2),
                    'count' => $txs->count(),
                    'share_pct' => $totalOutgoing > 0 ? round(($amount / $totalOutgoing) * 100, 1) : 0.0,
                    'aliases' => $aliases,
                    'details' => $details,
                    'details_total' => count($details),
                    'details_duplicates_merged' => max(0, $detailsRaw->count() - count($details)),
                    'is_recurring_noise' => $isRecurringNoise,
                    'filter_q' => $filterQuery,
                ];
            })
            ->values();

        $beneficiaryConcentrationExcludedCount = $beneficiaryConcentrationRaw
            ->where('is_recurring_noise', true)
            ->count();

        $beneficiaryConcentration = $beneficiaryConcentrationRaw
            ->reject(fn ($row) => (bool) ($row['is_recurring_noise'] ?? false))
            ->sortByDesc('amount')
            ->take(12)
            ->values();

        // =========================================================
        // SOURCE CONCENTRATION (crédits) — qui envoie, nature, suivi des fonds
        // =========================================================
        $incomingForConc = $allTxWithDate->where('type', 'credit')
            ->filter(function ($t) use ($internalTransferPatterns) {
                // Exclude credits that are merely internal transfers between own accounts
                $raw = trim((string) ($t->destination ?: $t->origin ?: $t->normalized_label ?: $t->label));
                if ($this->isInternalTransferLabel($raw)) {
                    return false;
                }

                $rawUp = mb_strtoupper($raw);
                foreach ($internalTransferPatterns as $pattern) {
                    if (str_contains($rawUp, $pattern)) {
                        return false;
                    }
                }
                return true;
            });
        $totalIncomingForConc = (float) $incomingForConc->sum(fn ($t) => abs((float) $t->amount));

        $venteKeywords = [
            'VENTE', 'CESSION', 'TUCSON', 'PICANTO', 'AUTO EUROPEAN', 'VOITURE', 'VEHICULE',
            'TERRAIN', 'IMMOBILIER', 'NOTAIRE', 'COMPROMIS', 'AGENCE IMMO', 'FONCIER',
            'LMNP', 'DONATION', 'HERITAGE', 'SUCCESSION', 'INDEMNISATION', 'LIQUIDATION',
        ];

        $sourceConcentrationRaw = $incomingForConc
            ->groupBy(function ($t) {
                $identity = $this->resolveSourceIdentity($t);

                return (string) ($identity['key'] ?? 'INCONNU');
            })
            ->map(function ($txs) use ($totalIncomingForConc, $allTxWithDate, $venteKeywords) {
                $amount   = (float) $txs->sum(fn ($t) => abs((float) $t->amount));
                $first    = $txs->first();
                $identity = $this->resolveSourceIdentity($first);
                $idKey    = (string) ($identity['key'] ?? 'INCONNU');

                $sampleText = mb_strtoupper(
                    $txs->map(fn ($t) => (string) ($t->normalized_label ?? $t->label ?? ''))->filter()->implode(' ')
                );

                // ── Nature classification ──────────────────────────────────
                $isVente = false;
                foreach ($venteKeywords as $kw) {
                    if (str_contains($sampleText, $kw)) {
                        $isVente = true;
                        break;
                    }
                }
                $isPonctuel = !$isVente && $txs->count() <= 2 && $amount >= 5000;
                $isNoise    = $txs->count() >= 150
                    && preg_match('/\b(COTISATION|FACTURE|CARTE)\b/u', $sampleText) === 1;

                // ── Nature : assurance (remboursements) ───────────────────
                // Identifé via la clé d'identité ou les mots-clés du libellé.
                $isAssurance = !$isVente && (
                    in_array($idKey, ['INST_MATMUT', 'INST_ASSURANCE'], true)
                    || preg_match('/\b(?:MATMUT|MAIF|AXA|ALLIANZ|GROUPAMA|GENERALI|MMA|TRAVAILLEUR\s+MUTUALISTE)\b/u', $sampleText) === 1
                    || preg_match('/\b(?:SINISTRE|INDEMNISATION|REGLEMENT\s+SIN|REGL\s+SIN|REMB\s+ASSURANCE)\b/u', $sampleText) === 1
                );

                // ── Nature : familial (virements intra-dossier GIORDANO) ──
                // Ces flux doivent apparaître séparément, pas dans les "revenus".
                $isFamilial = !$isVente && !$isAssurance && preg_match(
                    '/^(?:PERSONNE_(?:LILIANE_GIORDANO_NOVAK|ANTHONY_GIORDANO|EMILIE_GIORDANO|M_GIORDANO)|COMPTE_COMMUN_GIORDANO|PERSONNE_GIORDANO_NOVAK)$/',
                    $idKey
                ) === 1;

                $nature = $isVente ? 'vente' : ($isAssurance ? 'assurance' : ($isFamilial ? 'familial' : ($isPonctuel ? 'ponctuel' : 'revenu')));
                $natureLabel = match ($nature) {
                    'vente'     => 'Vente / cession',
                    'assurance' => 'Remboursement assurance',
                    'familial'  => 'Virement familial (intra-dossier)',
                    'ponctuel'  => 'Paiement ponctuel',
                    default     => 'Revenu / régulier',
                };

                // ── Aliases ────────────────────────────────────────────────
                $aliases = $txs
                    ->map(fn ($t) => Normalization::cleanLabel(trim((string) ($t->origin ?: $t->destination ?: $t->normalized_label ?: $t->label))))
                    ->filter(fn ($v) => $v !== '')
                    ->unique()->take(5)->values()->all();

                // ── Transaction details ────────────────────────────────────
                $detailsRaw = $txs
                    ->sortByDesc(fn ($t) => optional($t->date)->format('Y-m-d').'#'.str_pad((string) ($t->id ?? 0), 10, '0', STR_PAD_LEFT))
                    ->map(function ($t) {
                        $display = $this->buildDisplayLabel((string) ($t->label ?? ''), 120);

                        return [
                            'id'             => (int) ($t->id ?? 0),
                            'date'           => optional($t->date)->format('Y-m-d'),
                            'type'           => 'credit',
                            'amount'         => round(abs((float) $t->amount), 2),
                            'label'          => $display['short'],
                            'label_full'     => $display['full'],
                            'label_truncated' => $display['truncated'],
                        ];
                    })->values();

                $details = $detailsRaw
                    ->groupBy(fn ($r) => ($r['date'].'|'.number_format((float) $r['amount'], 2, '.', '').'|credit|'.mb_strtoupper((string) ($r['label_full'] ?? $r['label'] ?? ''))))
                    ->map(fn ($rows) => array_merge($rows->first(), ['duplicate_count' => $rows->count()]))
                    ->values()->all();

                // ── Suivi des fonds (ventes / ponctuels) ──────────────────
                $fundTracking = [];
                if ($nature !== 'revenu') {
                    foreach ($txs->sortByDesc(fn ($t) => abs((float) $t->amount))->take(3) as $creditTx) {
                        $creditAmt     = abs((float) $creditTx->amount);
                        $creditDateStr = optional($creditTx->date)->format('Y-m-d') ?? '';
                        if ($creditAmt < 3000 || $creditDateStr === '') {
                            continue;
                        }
                        $windowEnd = (new \DateTime($creditDateStr))->modify('+90 days')->format('Y-m-d');

                        $subsequent = $allTxWithDate
                            ->where('type', 'debit')
                            ->filter(fn ($d) =>
                                optional($d->date)->format('Y-m-d') > $creditDateStr
                                && optional($d->date)->format('Y-m-d') <= $windowEnd
                                && abs((float) $d->amount) >= $creditAmt * 0.03
                            )
                            ->groupBy(fn ($d) => $this->resolveBeneficiaryIdentity($d)['key'])
                            ->map(fn ($debits) => [
                                'beneficiary' => $this->resolveBeneficiaryIdentity($debits->first())['label'],
                                'amount'      => round($debits->sum(fn ($d) => abs((float) $d->amount)), 2),
                                'count'       => $debits->count(),
                            ])
                            ->sortByDesc('amount')->take(6)->values()->all();

                        if (!empty($subsequent)) {
                            $fundTracking[] = [
                                'credit_id'     => (int) ($creditTx->id ?? 0),
                                'credit_date'   => $creditDateStr,
                                'credit_amount' => round($creditAmt, 2),
                                'window_days'   => 90,
                                'destinations'  => $subsequent,
                            ];
                        }
                    }
                }

                return [
                    'key'               => $idKey,
                    'source'            => (string) ($identity['label'] ?? 'INCONNU'),
                    'amount'            => round($amount, 2),
                    'count'             => $txs->count(),
                    'share_pct'         => $totalIncomingForConc > 0 ? round(($amount / $totalIncomingForConc) * 100, 1) : 0.0,
                    'nature'            => $nature,
                    'nature_label'      => $natureLabel,
                    'aliases'           => $aliases,
                    'details'           => $details,
                    'details_total'     => count($details),
                    'fund_tracking'     => $fundTracking,
                    'is_recurring_noise' => $isNoise,
                    'filter_q'          => trim((string) (($aliases[0] ?? '') ?: ($identity['label'] ?? ''))),
                ];
            })->values();

        $sourceConcentrationExcludedCount = $sourceConcentrationRaw->where('is_recurring_noise', true)->count();
        $sourceConcentration = $sourceConcentrationRaw
            ->reject(fn ($r) => (bool) ($r['is_recurring_noise'] ?? false))
            ->sortByDesc('amount')
            ->take(14)
            ->values();

        $totalIncomingHorsVentes = round($sourceConcentration->whereNotIn('nature', ['vente', 'assurance', 'familial'])->sum(fn ($r) => (float) ($r['amount'] ?? 0)), 2);
        $totalIncomingVentes     = round($sourceConcentration->where('nature', 'vente')->sum(fn ($r) => (float) ($r['amount'] ?? 0)), 2);
        $totalIncomingAssurance  = round($sourceConcentration->where('nature', 'assurance')->sum(fn ($r) => (float) ($r['amount'] ?? 0)), 2);
        $totalIncomingFamilial   = round($sourceConcentration->where('nature', 'familial')->sum(fn ($r) => (float) ($r['amount'] ?? 0)), 2);

        $anomalyMonthsCount = (int) $monthlyTotals
            ->filter(fn ($row) => !empty($row['credit_anomaly']) || !empty($row['debit_anomaly']))
            ->count();
        $sensitiveChangePct = (float) ($sensitiveStats['change_pct'] ?? 0);
        $topBeneficiaryShare = (float) (($beneficiaryConcentration->first()['share_pct'] ?? 0));
        $exceptionalCount = (int) $exceptionalTransactions->count();

        $pointsAnomaly = min(12, $anomalyMonthsCount * 4);
        $pointsSensitive = $sensitiveStats === null
            ? 0
            : min(8, max(0, (int) round((abs($sensitiveChangePct) / 30) * 8)));
        $pointsBeneficiary = min(6, max(0, (int) round(($topBeneficiaryShare / 30) * 6)));

        $globalScore = (int) ($case->global_score ?? 0);
        if ($globalScore > 0) {
            $baseSum = $pointsAnomaly + $pointsSensitive + $pointsBeneficiary;
            if ($baseSum > $globalScore && $baseSum > 0) {
                $ratio = $globalScore / $baseSum;
                $pointsAnomaly = (int) floor($pointsAnomaly * $ratio);
                $pointsSensitive = (int) floor($pointsSensitive * $ratio);
                $pointsBeneficiary = (int) floor($pointsBeneficiary * $ratio);
                $baseSum = $pointsAnomaly + $pointsSensitive + $pointsBeneficiary;
            }
            $pointsExceptional = min(11, max(0, $globalScore - $baseSum));
        } else {
            $pointsExceptional = min(11, max(0, (int) round(($exceptionalCount / 5) * 11)));
            $globalScore = $pointsAnomaly + $pointsSensitive + $pointsBeneficiary + $pointsExceptional;
        }

        // ── Legal risk level (distinct from statistical anomaly) ──────────────
        $statisticalScore = $pointsAnomaly + $pointsSensitive + $pointsBeneficiary;
        $hasHighConcentration = $topBeneficiaryShare >= 50;
        $hasHighExceptional   = $exceptionalCount >= 3;
        $hasCriticalPics      = $anomalyMonthsCount >= 3;
        if (($pointsExceptional >= 8 && $pointsBeneficiary >= 4) || ($globalScore >= 70)) {
            $legalRisk = ['level' => 'elevated', 'label' => 'Risque juridique potentiel élevé', 'color' => '#991B1B', 'bg' => '#FEF2F2', 'border' => '#FECACA',
                'note' => 'Plusieurs indicateurs convergents (montants, concentration, pics). Vérification des justificatifs recommandée.'];
        } elseif ($pointsExceptional >= 4 || $hasCriticalPics || $hasHighConcentration) {
            $legalRisk = ['level' => 'moderate', 'label' => 'Vigilance juridique modérée', 'color' => '#92400E', 'bg' => '#FFFBEB', 'border' => '#FDE68A',
                'note' => 'Quelques signaux notables. Analyse contextuelle utile pour qualifier les flux.'];
        } else {
            $legalRisk = ['level' => 'low', 'label' => 'Risque juridique faible', 'color' => '#065F46', 'bg' => '#F0FDF4', 'border' => '#BBF7D0',
                'note' => 'Aucun signal juridique fort. Profil cohérent avec un usage courant.'];
        }

        $scoreBreakdown = [
            'total'            => $globalScore,
            'statistical_score'=> $statisticalScore,
            'legal_risk'       => $legalRisk,
            'items' => [
                [
                    'label'       => 'Pics mensuels atypiques',
                    'icon'        => '📊',
                    'points'      => $pointsAnomaly,
                    'max'         => 12,
                    'description' => $anomalyMonthsCount > 0
                        ? "{$anomalyMonthsCount} mois avec z-score ≥ 2σ détectés"
                        : 'Aucun mois atypique détecté',
                ],
                [
                    'label'       => 'Variation fenêtre sensible',
                    'icon'        => '📅',
                    'points'      => $pointsSensitive,
                    'max'         => 8,
                    'description' => $sensitiveStats !== null
                        ? 'Variation de '.number_format(abs($sensitiveChangePct), 1, ',', ' ').'% sur période sensible'
                        : 'Période sensible non identifiée',
                ],
                [
                    'label'       => 'Concentration bénéficiaire',
                    'icon'        => '👤',
                    'points'      => $pointsBeneficiary,
                    'max'         => 6,
                    'description' => number_format($topBeneficiaryShare, 1, ',', ' ').'% des débits vers un seul bénéficiaire',
                ],
                [
                    'label'       => 'Montants exceptionnels',
                    'icon'        => '⚡',
                    'points'      => $pointsExceptional,
                    'max'         => 11,
                    'description' => "{$exceptionalCount} transaction(s) hors norme détectée(s)",
                ],
            ],
        ];

        $coherenceCandidates = $exceptionalTransactions
            ->sortByDesc(fn ($t) => abs((float) ($t->amount ?? 0)))
            ->take(5)
            ->values();

        $saleKeywords = '/\b(VENTE|TERRAIN|NOTAIRE|CESSION|IMMOBILIER)\b/u';
        $insuranceKeywords = '/\b(ASSURANCE|MUTUELLE|MUTUALISTE|SINISTRE|INDEMNISATION|INDEMNIT[ÉE]|REMBOURSEMENT)\b/u';

        $saleHits = 0;
        $insuranceHits = 0;
        foreach ($coherenceCandidates as $candidate) {
            $sample = mb_strtoupper((string) ($candidate->display_label_full ?? $candidate->display_label ?? $candidate->label ?? ''));
            if ($sample !== '' && preg_match($saleKeywords, $sample) === 1) {
                $saleHits++;
            }
            if ($sample !== '' && preg_match($insuranceKeywords, $sample) === 1) {
                $insuranceHits++;
            }
        }

        $coherenceNarrative = 'Lecture économique préliminaire: le moteur ne produit pas de qualification juridique, seulement des hypothèses à vérifier par pièces.';
        if ($saleHits > 0 && $insuranceHits > 0) {
            $coherenceNarrative = 'Les flux majeurs contiennent des indices compatibles avec une cession d\'actif (ex. terrain) et des remboursements d\'assurance liés à sinistre/incendie. Cette lecture doit être confirmée par documents (acte de vente, décompte assureur, relevés).';
        } elseif ($insuranceHits > 0) {
            $coherenceNarrative = 'Les flux majeurs comportent des marqueurs d\'indemnisation/remboursement d\'assurance. Interprétation descriptive à confirmer par justificatifs (sinistre, décompte assureur, crédits correspondants).';
        } elseif ($saleHits > 0) {
            $coherenceNarrative = 'Les flux majeurs comportent des marqueurs de cession d\'actif (vente/terrain). Interprétation descriptive à confirmer par justificatifs (acte, notaire, chronologie des mouvements).';
        }

        $coherenceScore = 0.0;
        if ($coherenceCandidates->isNotEmpty()) {
            $coherenceScore = 25.0;
            if ($saleHits > 0) {
                $coherenceScore += 35.0;
            }
            if ($insuranceHits > 0) {
                $coherenceScore += 30.0;
            }
            if ($coherenceCandidates->count() >= 3) {
                $coherenceScore += 10.0;
            }
        }
        $coherenceScore = min(100.0, $coherenceScore);

        $economicCoherence = [
            'coherence_score' => round($coherenceScore, 1),
            'examples' => $coherenceCandidates->map(function ($t) {
                return [
                    'label' => (string) ($t->display_label ?? $t->label ?? 'Flux important'),
                    'amount' => round(abs((float) ($t->amount ?? 0)), 2),
                    'type' => (string) ($t->type ?? 'debit'),
                    'date' => optional($t->date)->format('Y-m-d'),
                ];
            })->values(),
            'narrative' => $coherenceNarrative,
        ];

        $analysisMeta = [
            'analysis_date' => optional($latestAnalysisResult?->generated_at)->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i'),
            'algorithm_version' => (string) (config('app.version') ?: env('ANALYTICA_ALGO_VERSION', 'Analytica-1.0')),
            'methodology' => 'Détection statistique sur séries temporelles (z-score, variations mensuelles, concentration bénéficiaires).',
            'rgpd' => 'Traitement limité au périmètre du dossier, conservation maîtrisée et accès restreint.',
            'neutrality' => 'Résultats descriptifs et probabilistes, sans qualification juridique automatique.',
        ];

        return view('cases.show', [
            'case' => $case,
            'reports' => $reports,
            'latest_pdf_report' => $latestPdfReport,
            'latest_analysis_result' => $latestAnalysisResult,
            'last_ai' => $lastAi,
            'date_coverage' => [
                'from' => $overallFrom ? Carbon::parse($overallFrom)->format('Y-m-d') : null,
                'to' => $overallTo ? Carbon::parse($overallTo)->format('Y-m-d') : null,
            ],
            'account_insights' => $accountInsights,
            'account_change_events' => $accountChangeEvents
                ->sortBy(fn ($event) => (string) ($event['date'] ?? '9999-12-31'))
                ->values(),
            'account_filter_options' => [
                'banks' => $case->bankAccounts->pluck('bank_name')->filter()->unique()->values(),
                'accounts' => $case->bankAccounts->map(fn ($account) => [
                    'id' => $account->getKey(),
                    'label' => $account->bank_name.' · '.($account->iban_masked ?: ('Compte #'.$account->getKey())),
                ])->values(),
            ],
            'account_display_by_id' => $accountDisplayById,
            'exceptional_threshold' => $exceptionalThreshold,
            'exceptional_threshold_raw' => $exceptionalThresholdRaw,
            'exceptional_transactions' => $exceptionalTransactions,
            'import_high_value_threshold' => $importHighValueThreshold,
            'statement_diagnostics' => $statementDiagnostics,
            'yearly_totals' => $yearlyTotals,
            'stats' => $stats,
            'transactions' => $transactions,
            'tx_filters' => $filters,
            'tx_totals' => [
                'debit' => round($totalDebit, 2),
                'credit' => round($totalCredit, 2),
                'net' => round($net, 2),
                'count' => (clone $txBase)->count(),
            ],
            'behavioral' => [
                'monthly_totals'  => $monthlyTotals,
                'monthly_max'     => $monthlyMax,
                'sensitive_stats' => $sensitiveStats,
                'top_spikes'      => $topSpikes,
                'spike_threshold' => round((float) $spikeThreshold, 2),
                'avg_credits'     => round($avgCredit, 2),
                'avg_debits'      => round($avgDebit,  2),
                'std_credits'     => round($stdCredit, 2),
                'std_debits'      => round($stdDebit,  2),
            ],
            'kind_monthly_breakdown' => $kindMonthlyBreakdown,
            'kind_peaks' => $kindPeaks,
            'beneficiary_concentration' => $beneficiaryConcentration,
            'beneficiary_concentration_excluded_count' => $beneficiaryConcentrationExcludedCount,
            'source_concentration'                => $sourceConcentration,
            'source_concentration_excluded_count' => $sourceConcentrationExcludedCount,
            'total_incoming_for_conc'             => round($totalIncomingForConc, 2),
            'total_incoming_hors_ventes'          => $totalIncomingHorsVentes,
            'total_incoming_ventes'               => $totalIncomingVentes,
            'total_incoming_assurance'            => $totalIncomingAssurance,
            'total_incoming_familial'             => $totalIncomingFamilial,
            'regular_inflows' => $regularInflows,
            'regular_outflows' => $regularOutflows,
            'regular_outliers' => $regularOutliers,
            'cash_monthly_totals' => $cashMonthlyTotals,
            'cash_monthly_average' => round($cashMonthlyAverage, 2),
            'cash_peak_threshold' => round($cashPeakThreshold, 2),
            'cash_peak_month' => $cashPeakMonth,
            'cash_top_withdrawals' => $cashTopWithdrawals,
            'analysis_meta' => $analysisMeta,
            'score_breakdown' => $scoreBreakdown,
            'economic_coherence'        => $economicCoherence,
            'beneficiary_disambiguation' => $disambiguationGroups,
            'beneficiary_overrides'      => $this->activeOverrides,
        ]);
    }

    public function storeBeneficiaryOverrides(Request $request, CaseFile $case)
    {
        Gate::authorize('update', $case);

        $validated = $request->validate([
            'overrides'                    => ['nullable', 'array'],
            'overrides.*.normalized_label' => ['required', 'string', 'max:2000'],
            'overrides.*.identity_key'     => ['required', 'string', 'max:100'],
        ]);

        if (empty($validated['overrides'])) {
            return redirect()->route('cases.show', $case)->with('status', 'Aucune modification à sauvegarder.');
        }

        $identityLabels = [
            'PERSONNE_ANTHONY_GIORDANO'     => 'M. Anthony GIORDANO',
            'PERSONNE_EMILIE_GIORDANO'      => 'Mme Emilie GIORDANO',
            'PERSONNE_M_GIORDANO'           => 'M. GIORDANO Christian',
            'PERSONNE_LILIANE_GIORDANO_NOVAK' => 'Mme Liliane GIORDANO / NOVAK',
            'COMPTE_COMMUN_GIORDANO'        => 'M. ou Mme GIORDANO (compte commun)',
            'PERSONNE_GIORDANO_NOVAK'       => 'Groupe GIORDANO / NOVAK (à ventiler)',
            'EXTERNE'                       => 'Externe / Tiers',
            'INCONNU'                       => 'Inconnu',
        ];

        $saved = 0;
        $deleted = 0;
        foreach ((array) ($validated['overrides'] ?? []) as $row) {
            $normalized = Normalization::normalizeLabel((string) ($row['normalized_label'] ?? ''));
            if ($normalized === '') {
                continue;
            }
            $key = (string) ($row['identity_key'] ?? '');

            // 'RESET' means: remove the override and let automatic rules decide
            if ($key === 'RESET' || $key === '') {
                BeneficiaryAliasOverride::where('case_id', $case->getKey())
                    ->where('normalized_label', $normalized)
                    ->delete();
                $deleted++;
                continue;
            }

            $label = $identityLabels[$key] ?? ucwords(strtolower(str_replace('_', ' ', $key)));

            BeneficiaryAliasOverride::updateOrCreate(
                ['case_id' => $case->getKey(), 'normalized_label' => $normalized],
                ['identity_key' => $key, 'identity_label' => $label]
            );
            $saved++;
        }

        $msg = "Corrections enregistrées : {$saved} assignation(s)";
        if ($deleted > 0) {
            $msg .= ", {$deleted} réinitialisée(s) (auto)";
        }
        $msg .= '.';

        return redirect()->route('cases.show', $case)->with('status', $msg);
    }

    public function exportTransactions(Request $request, CaseFile $case)
    {
        Gate::authorize('view', $case);

        $case->load(['bankAccounts.statements']);
        $filters = $this->buildTransactionFilters($request);

        $accountProfileById = $case->bankAccounts->mapWithKeys(function ($account) {
            $texts = $account->statements
                ->pluck('extracted_text')
                ->filter(fn ($t) => is_string($t) && trim($t) !== '')
                ->values()
                ->all();

            $holders = collect();
            if (!empty($account->account_holder)) {
                $holders->push(Normalization::cleanLabel((string) $account->account_holder));
            }
            foreach ($texts as $text) {
                foreach ($this->extractHolderMentionsFromText((string) $text) as $holder) {
                    $holders->push($holder);
                }
            }

            $isJoint = $this->detectJointHolderProfile((string) ($account->account_holder ?? ''), $texts, $holders->unique()->values()->all());

            return [$account->getKey() => $isJoint ? 'joint' : 'personal'];
        });

        $selectedAccounts = $case->bankAccounts;
        if ($filters['bank_name'] !== '') {
            $selectedAccounts = $selectedAccounts->where('bank_name', $filters['bank_name']);
        }

        if ($filters['account_profile'] === 'joint' || $filters['account_profile'] === 'personal') {
            $filtered = $selectedAccounts->filter(function ($account) use ($accountProfileById, $filters) {
                return ($accountProfileById[$account->getKey()] ?? 'personal') === $filters['account_profile'];
            })->values();
            if ($filtered->isNotEmpty()) {
                $selectedAccounts = $filtered;
            } else {
                $filters['account_profile'] = '';
            }
        }

        if ($filters['bank_account_id'] !== '' && ctype_digit($filters['bank_account_id'])) {
            $selectedAccounts = $selectedAccounts->where('id', (int) $filters['bank_account_id']);
        }

        $accountIds = $selectedAccounts->pluck('id');
        $txBase = Transaction::query()->whereIn('bank_account_id', $accountIds);
        $this->applyTransactionFilters($txBase, $filters);

        $transactions = (clone $txBase)
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $filename = sprintf('analytica-case-%d-transactions-filtres-%s.xlsx', $case->getKey(), now()->format('Ymd_His'));

        return Excel::download(new CaseTransactionsExport($case, $transactions), $filename, ExcelFormat::XLSX);
    }

    public function downloadStatement(Request $request, CaseFile $case, Statement $statement, EncryptedFileStorage $storage)
    {
        Gate::authorize('view', $case);

        // Ensure the statement belongs to this case.
        $case->load('bankAccounts');
        $accountIds = $case->bankAccounts->pluck('id');
        if (!$accountIds->contains($statement->bank_account_id)) {
            abort(403, 'Ce relevé n\'appartient pas à ce dossier.');
        }

        if (empty($statement->file_path)) {
            abort(404, 'Aucun fichier stocké pour ce relevé.');
        }

        $bytes    = $storage->getDecryptedBytes($statement->file_path, $statement->encryption_meta ?? []);
        $filename = $statement->original_filename ?? ('releve-' . $statement->getKey() . '.pdf');
        $mime     = $statement->mime_type ?? 'application/pdf';

        // Sanitise filename for Content-Disposition.
        $safeFilename = str_replace(['"', '\\'], '', $filename);

        return response($bytes, 200, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline; filename="' . $safeFilename . '"',
            'Content-Length'      => strlen($bytes),
            'Cache-Control'       => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function analyze(Request $request, CaseFile $case, AnalysisEngine $engine, AiAssistant $assistant)
    {
        Gate::authorize('update', $case);

        if (is_null($case->death_date)) {
            return redirect()
                ->route('cases.show', $case)
                ->with('analysis_error', 'Date de décès obligatoire pour lancer une analyse des derniers mois. Renseigne-la d’abord dans le dossier.');
        }

        $validated = $request->validate([
            'analysis_scope' => ['nullable', 'in:all,period'],
            'analysis_from' => ['nullable', 'date'],
            'analysis_to' => ['nullable', 'date', 'after_or_equal:analysis_from'],
        ]);

        $scope = (string) ($validated['analysis_scope'] ?? 'all');
        $rangeStart = null;
        $rangeEnd = null;

        if ($scope === 'period') {
            if (empty($validated['analysis_from']) || empty($validated['analysis_to'])) {
                return redirect()
                    ->route('cases.show', $case)
                    ->with('analysis_error', 'Pour une analyse sur période, renseigne une date de début et de fin.');
            }

            $rangeStart = Carbon::parse((string) $validated['analysis_from'])->startOfDay();
            $rangeEnd = Carbon::parse((string) $validated['analysis_to'])->endOfDay();
        }

        $precheck = $this->buildTransferBeneficiaryPrecheck($case, $rangeStart, $rangeEnd);
        $precheckWarning = null;
        if (($precheck['total'] ?? 0) > 0 && ($precheck['ambiguous'] ?? 0) > 0) {
            $examples = collect((array) ($precheck['examples'] ?? []))->take(3)->implode(' | ');
            $precheckWarning = sprintf(
                'Pré-classement virements: %d détectés, %d classés automatiquement, %d ambigus. %s',
                (int) ($precheck['total'] ?? 0),
                (int) ($precheck['classified'] ?? 0),
                (int) ($precheck['ambiguous'] ?? 0),
                $examples !== '' ? 'Exemples ambigus: '.$examples : 'Ajuste les alias si nécessaire.'
            );
        }

        $result = $engine->analyzeCase($case->fresh(['bankAccounts']), $rangeStart, $rangeEnd);

        $case->forceFill([
            'status' => 'completed',
            'analysis_period_start' => $scope === 'period' ? $rangeStart?->toDateString() : null,
            'analysis_period_end' => $scope === 'period' ? $rangeEnd?->toDateString() : null,
        ])->save();

        $generatedAt = optional($result->generated_at)->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i');
        $scopeLabel = $scope === 'period'
            ? 'période '.$rangeStart?->format('d/m/Y').' → '.$rangeEnd?->format('d/m/Y')
            : 'historique complet';

        $aiStatus = null;
        if ((bool) config('analytica.ai.enabled', false) && (bool) config('analytica.ai.auto_after_case_analysis', true)) {
            try {
                $case->load(['bankAccounts.statements']);
                $accountIds = $case->bankAccounts()->pluck('id');
                $max = (int) config('analytica.ai.max_transactions', 300);

                $transactions = Transaction::query()
                    ->whereIn('bank_account_id', $accountIds)
                    ->orderByDesc('date')
                    ->orderByDesc('id')
                    ->limit($max)
                    ->get([
                        'date', 'amount', 'type', 'kind', 'origin', 'destination', 'motif', 'cheque_number', 'label', 'normalized_label', 'anomaly_score',
                    ])
                    ->map(function ($t) {
                        return [
                            'date' => optional($t->date)->format('Y-m-d'),
                            'amount' => (float) $t->amount,
                            'type' => (string) $t->type,
                            'kind' => $t->kind,
                            'origin' => $t->origin,
                            'destination' => $t->destination,
                            'motif' => $t->motif,
                            'cheque_number' => $t->cheque_number,
                            'label' => $t->label,
                            'normalized_label' => $t->normalized_label,
                            'anomaly_score' => $t->anomaly_score,
                        ];
                    })
                    ->values()
                    ->all();

                $statements = $case->bankAccounts
                    ->flatMap(fn ($a) => $a->statements)
                    ->sortByDesc('created_at')
                    ->take(10)
                    ->map(fn ($s) => [
                        'filename' => $s->original_filename,
                        'status' => $s->import_status,
                        'transactions_imported' => $s->transactions_imported,
                        'ocr_used' => (bool) $s->ocr_used,
                        'message' => $s->import_error,
                        'created_at' => optional($s->created_at)->toIso8601String(),
                    ])
                    ->values()
                    ->all();

                $context = [
                    'transactions_count' => count($transactions),
                    'transactions' => $transactions,
                    'recent_statements' => $statements,
                ];

                $autoPrompt = 'Synthèse automatique après analyse dossier.';
                $aiResult = $assistant->analyzeCase($case, $context, $autoPrompt);

                $case->forceFill([
                    'ai_last_prompt' => $autoPrompt,
                    'ai_last_result' => $aiResult,
                    'ai_last_error' => null,
                    'ai_last_ran_at' => now(),
                ])->save();

                $request->session()->put('cases.'.$case->getKey().'.last_ai', [
                    'prompt' => $autoPrompt,
                    'result' => $aiResult,
                    'ran_at' => now()->toIso8601String(),
                ]);

                $aiStatus = 'IA: compte rendu généré';
            } catch (\Throwable $e) {
                $case->forceFill([
                    'ai_last_prompt' => 'Synthèse automatique après analyse dossier.',
                    'ai_last_error' => $e->getMessage(),
                    'ai_last_ran_at' => now(),
                ])->save();

                $request->session()->put('cases.'.$case->getKey().'.last_ai', [
                    'prompt' => 'Synthèse automatique après analyse dossier.',
                    'error' => $e->getMessage(),
                    'ran_at' => now()->toIso8601String(),
                ]);

                $aiStatus = 'IA indisponible ('.$e->getMessage().')';
            }
        }

        $statusMessage = 'Analyse générée le '.$generatedAt.' ('.$scopeLabel.', score '.$result->global_score.', anomalies '.$result->total_flagged.').';
        if (is_string($aiStatus) && $aiStatus !== '') {
            $statusMessage .= ' '.$aiStatus.'.';
        }

        $redirect = redirect()->route('cases.show', $case)->with('status', $statusMessage);
        if (is_string($precheckWarning) && $precheckWarning !== '') {
            $redirect->with('analysis_warning', $precheckWarning);
        }

        return $redirect;
    }

    /**
     * @return array<int,string>
     */
    private function extractAccountIdentifiersFromText(string $text): array
    {
        $upper = mb_strtoupper($text);
        $matches = [];

        preg_match_all('/\bFR\s*\d{2}(?:\s*[A-Z0-9]){11,30}\b/u', $upper, $ibanMatches);
        foreach (($ibanMatches[0] ?? []) as $iban) {
            $matches[] = (string) $iban;
        }

        preg_match_all('/\bIBAN\s*[:\-]?\s*([A-Z0-9\s]{15,40})\b/u', $upper, $ibanLabeled);
        foreach (($ibanLabeled[1] ?? []) as $iban) {
            $matches[] = (string) $iban;
        }

        preg_match_all('/\bRIB\s*[:\-]?\s*([0-9OIL\s]{12,30})\b/u', $upper, $ribMatches);
        foreach (($ribMatches[1] ?? []) as $rib) {
            $matches[] = (string) $rib;
        }

        return array_values(array_filter($matches, fn ($value) => trim($value) !== ''));
    }

    private function normalizeAccountIdentifier(string $raw): string
    {
        $value = mb_strtoupper($raw);
        $value = strtr($value, [
            'O' => '0',
            'I' => '1',
            'L' => '1',
        ]);

        return preg_replace('/[^A-Z0-9]/', '', $value) ?? '';
    }

    private function formatAccountIdentifier(string $identifier): string
    {
        $id = trim($identifier);
        if ($id === '') {
            return '—';
        }

        $len = mb_strlen($id);
        if ($len <= 10) {
            return $id;
        }

        return mb_substr($id, 0, 4).'…'.mb_substr($id, -4);
    }

    /**
     * @return array<int,float>
     */
    private function extractHighValueAmountsFromText(string $text, float $threshold): array
    {
        $lines = preg_split('/\R/u', $text) ?: [];
        $values = [];

        foreach ($lines as $line) {
            $clean = Normalization::cleanLabel((string) $line);
            if ($clean === '') {
                continue;
            }

            if ($this->isLikelyMetadataLineForDiagnostics($clean)) {
                continue;
            }

            $upper = mb_strtoupper($clean);
            $hasDateLike = preg_match('/^\s*\d{2}[\/\-.]\d{2}(?:[\/\-.]\d{2,4})?\b/u', $clean) === 1;
            $hasTxnHint = preg_match('/\b(VER(?:EMENT)?|VIR(?:EMENT)?|PRLV|SEPA|CHEQUE|CH[ÉE]QUE|RETRAIT|REMBOURST|FACTURE|CARTE|ECHEANCE|ÉCHÉANCE)\b/u', $upper) === 1;
            if (! $hasDateLike && ! $hasTxnHint) {
                continue;
            }

            preg_match_all('/-?(?:\d{1,3}(?:[\s\x{00A0}.]\d{3})+|\d+)(?:[\.,]\d{2})|-?\d{1,3}(?:[\s\x{00A0}.]\d{3})+(?:\s?(?:€|EUR))?/u', $clean, $m);

            foreach (($m[0] ?? []) as $raw) {
                $token = preg_replace('/\s?(€|EUR)$/iu', '', trim((string) $raw)) ?? trim((string) $raw);
                if ($token === '' || preg_match('/^\d{2}[\/\-.]\d{2}$/', $token)) {
                    continue;
                }

                $amount = Normalization::parseAmount($token);
                if ($amount === null) {
                    continue;
                }

                $value = abs((float) $amount);
                if ($value >= $threshold) {
                    $values[] = round($value, 2);
                }
            }
        }

        $values = array_values(array_unique($values));
        rsort($values, SORT_NUMERIC);

        return $values;
    }

    /**
     * @return array{start:string,end:string}|null
     */
    private function extractStatementPeriodFromText(string $text): ?array
    {
        $normalized = mb_strtolower($text);
        $normalized = str_replace(["\n", "\r"], ' ', $normalized);
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        if (preg_match('/du\s+(\d{2}[\/\-.]\d{2}[\/\-.]\d{2,4})\s+au\s+(\d{2}[\/\-.]\d{2}[\/\-.]\d{2,4})/u', $normalized, $m)) {
            try {
                $start = Carbon::parse($m[1])->toDateString();
                $end = Carbon::parse($m[2])->toDateString();
                return ['start' => $start, 'end' => $end];
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    private function isLikelyMetadataLineForDiagnostics(string $line): bool
    {
        $upper = mb_strtoupper($line);

        if (preg_match('/\b(SOLDE|TOTAL\s+(DEBIT|DÉBIT|CREDIT|CRÉDIT)|ANCIEN\s+SOLDE|NOUVEAU\s+SOLDE)\b/u', $upper)) {
            return true;
        }

        if (preg_match('/\b(IBAN|BIC|RIB|RELEVE\s+DE\s+COMPTE|RELEVÉ\s+DE\s+COMPTE|MONNAIE\s+DU\s+COMPTE)\b/u', $upper)) {
            return true;
        }

        return false;
    }

    /**
     * @return array<int,string>
     */
    private function extractHolderMentionsFromText(string $text): array
    {
        $normalized = mb_strtoupper($text);
        $normalized = strtr($normalized, [
            ':' => ' ',
            '.' => ' ',
            '/' => ' ',
            '-' => ' ',
            '\\' => ' ',
        ]);
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        preg_match_all('/\b(?:M|MR|MME|MONSIEUR|MADAME)\s+[A-Z][A-Z\s]{2,40}\b/u', $normalized, $matches);

        $holders = collect($matches[0] ?? [])
            ->map(fn ($value) => Normalization::cleanLabel((string) $value))
            ->filter(fn ($value) => mb_strlen($value) >= 5)
            ->unique()
            ->values()
            ->all();

        return $holders;
    }

    /**
     * @param array<int,string> $texts
     * @param array<int,string> $holders
     */
    private function detectJointHolderProfile(string $accountHolder, array $texts, array $holders): bool
    {
        $holder = mb_strtoupper($accountHolder);
        if (preg_match('/\b(OU|ET)\b/u', $holder) && preg_match('/\b(MME|MADAME|MONSIEUR|MR|M)\b/u', $holder)) {
            return true;
        }

        foreach ($texts as $text) {
            $upper = mb_strtoupper($text);
            if (preg_match('/\bM\b.*\b(OU|ET)\b.*\b(MME|MADAME)\b/u', $upper)) {
                return true;
            }
            if (preg_match('/\b(MONSIEUR|MR)\b.*\b(OU|ET)\b.*\b(MADAME|MME)\b/u', $upper)) {
                return true;
            }
        }

        $containsMale = collect($holders)->contains(fn ($h) => preg_match('/\b(M|MR|MONSIEUR)\b/u', mb_strtoupper((string) $h)) === 1);
        $containsFemale = collect($holders)->contains(fn ($h) => preg_match('/\b(MME|MADAME)\b/u', mb_strtoupper((string) $h)) === 1);

        return $containsMale && $containsFemale;
    }

    private function buildRegularSeries(Collection $transactions): Collection
    {
        $series = $transactions
            ->filter(fn ($t) => !is_null($t->date) && in_array((string) ($t->type ?? ''), ['credit', 'debit'], true))
            ->groupBy(function ($t) {
                $type = (string) ($t->type ?? 'debit');
                $kind = (string) ($t->kind ?? 'card');
                if (!in_array($kind, ['transfer', 'cheque', 'cash_withdrawal', 'card'], true)) {
                    $kind = $type === 'credit' ? 'transfer' : 'card';
                }

                $counterparty = $this->resolveCounterpartyForRegularSeries($t, $type);
                $counterpartyKey = Normalization::normalizeLabel($counterparty);

                return implode('|', [$type, $kind, $counterpartyKey]);
            })
            ->map(function ($rows, $key) {
                $rows = collect($rows)->sortBy('date')->values();
                $parts = explode('|', (string) $key);
                $type = (string) ($parts[0] ?? 'debit');
                $kind = (string) ($parts[1] ?? 'card');
                $months = $rows
                    ->map(fn ($t) => optional($t->date)->format('Y-m'))
                    ->filter()
                    ->unique()
                    ->values();

                if ($rows->count() < 3 || $months->count() < 3) {
                    return null;
                }

                $amounts = $rows->map(fn ($t) => abs((float) $t->amount))->values();
                $average = (float) ($amounts->avg() ?? 0);
                $median = $this->computeMedian($amounts);
                $min = (float) ($amounts->min() ?? 0);
                $max = (float) ($amounts->max() ?? 0);

                $std = 0.0;
                if ($amounts->count() > 1) {
                    $std = sqrt((float) $amounts->map(fn ($v) => pow((float) $v - $average, 2))->avg());
                }

                $frequency = $months->count() > 0 ? $rows->count() / $months->count() : 0;
                $variation = $average > 0 ? ($std / $average) : 0;

                if ($frequency < 0.45) {
                    return null;
                }

                if ($variation > 0.65 && $rows->count() < 6) {
                    return null;
                }

                $outliers = $rows
                    ->filter(function ($t) use ($median) {
                        $amount = abs((float) $t->amount);
                        if ($median <= 0) {
                            return false;
                        }

                        return $amount > ($median * 1.35) || $amount < ($median * 0.65);
                    })
                    ->map(fn ($t) => [
                        'date' => optional($t->date)->format('Y-m-d'),
                        'amount' => abs((float) $t->amount),
                        'label' => (string) ($t->label ?? ''),
                    ])
                    ->values()
                    ->all();

                $counterparty = $this->resolveCounterpartyForRegularSeries($rows->first(), $type);

                // Concaténer tous les libellés bruts de la série pour détecter des mots-clés
                // présents dans les motifs OCR (ex: SINISTRE, REGL) même si le champ
                // origin/destination ne les contient pas.
                $rawSampleText = $rows
                    ->map(fn ($t) => (string) ($t->label ?? $t->normalized_label ?? ''))
                    ->filter()
                    ->implode(' ');

                $incomeCategory = $this->resolveIncomeCategory($counterparty, $type, $rawSampleText);

                // Exclure les prélèvements (PRLV) qui apparaissent en crédit :
                // ce sont des remboursements de trop-perçu ou erreurs d'import,
                // pas des revenus réguliers (EDF, opérateurs télécom...).
                if ($type === 'credit') {
                    $cpUp = mb_strtoupper(trim($counterparty));
                    $labelUp = mb_strtoupper(trim((string) ($rows->first()->label ?? '')));
                    if (
                        preg_match('/^PRLV\b/', $cpUp) === 1
                        || preg_match('/^PRLV\b/', $labelUp) === 1
                        || preg_match('/^PRELEVEMENT\b/', $cpUp) === 1
                    ) {
                        return null; // exclure : ce n'est pas un revenu
                    }
                }

                return [
                    'type' => $type,
                    'kind' => $kind,
                    'kind_label' => $this->kindLabel($kind),
                    'income_category' => $incomeCategory,
                    'counterparty' => $counterparty,
                    'occurrences' => $rows->count(),
                    'months' => $months->count(),
                    'monthly_frequency' => round($frequency, 2),
                    'avg_amount' => round($average, 2),
                    'median_amount' => round($median, 2),
                    'min_amount' => round($min, 2),
                    'max_amount' => round($max, 2),
                    'last_date' => optional($rows->last()->date)->format('Y-m-d'),
                    'outliers' => $outliers,
                ];
            })
            ->filter()
            ->sortByDesc(fn ($row) => (($row['occurrences'] ?? 0) * 1000000) + ((float) ($row['avg_amount'] ?? 0)));

        return $series->values();
    }

    private function resolveCounterpartyForRegularSeries(object $transaction, string $type): string
    {
        $origin = trim((string) ($transaction->origin ?? ''));
        $destination = trim((string) ($transaction->destination ?? ''));
        $motif = trim((string) ($transaction->motif ?? ''));
        $label = trim((string) ($transaction->normalized_label ?? $transaction->label ?? ''));

        if ($type === 'credit') {
            $candidate = $origin !== '' ? $origin : ($destination !== '' ? $destination : $label);
        } else {
            $candidate = $destination !== '' ? $destination : ($origin !== '' ? $origin : $label);
        }

        if ($candidate === '' && $motif !== '') {
            $candidate = $motif;
        }

        return $candidate !== '' ? Normalization::cleanLabel($candidate) : 'Contrepartie inconnue';
    }

    private function kindLabel(string $kind): string
    {
        return match ($kind) {
            'transfer' => 'Virement',
            'cheque' => 'Chèque',
            'cash_withdrawal' => 'Espèces',
            'card' => 'Carte',
            default => 'Mouvement',
        };
    }

    /**
     * Classify a series counterparty into a human-readable income category.
     * Works for both credit and debit series.
     * $rawSample = concaténation des libellés bruts de la série (utile pour détecter
     *   les mots-clés présents dans les motifs OCR, ex. SINISTRE, REGL).
     *
     * @return array{key:string,label:string}|null
     */
    private function resolveIncomeCategory(string $counterparty, string $type, string $rawSample = ''): ?array
    {
        $n      = mb_strtoupper(trim($counterparty));
        $sample = mb_strtoupper($rawSample); // libell\u00e9s bruts concat\u00e9n\u00e9s : utile pour d\u00e9tecter des noms absents du champ origin

        // ---------------------------------------------------------------
        // MATMUT (Mutuelle d'Assurance des Travailleurs Mutualistes)
        // Traite les deux sens (cr\u00e9dit = remboursement, d\u00e9bit = cotisation).
        // ---------------------------------------------------------------
        $isMatmut = preg_match('/\bMATMUT\b/', $n) === 1
            || preg_match('/\bTRAVAILLEURS?\s+MUTUALISTES?\b/u', $n) === 1
            || preg_match('/\bMATMUT\b/', $sample) === 1
            || preg_match('/\bTRAVAILLEURS?\s+MUTUALISTES?\b/u', $sample) === 1;

        if ($isMatmut) {
            if ($type === 'credit') {
                return ['key' => 'remboursement_assurance_matmut', 'label' => 'Remboursement assurance (MATMUT)'];
            }
            // Débit = cotisation vers la MATMUT
            return ['key' => 'cotisation_assurance_matmut', 'label' => 'Cotisation assurance (MATMUT)'];
        }

        // Les catégories suivantes ne s'appliquent qu'aux crédits.
        if ($type !== 'credit') {
            return null;
        }

        // --- Retraite régime minier ---
        // ANGDM = Agence Nationale pour la Garantie des Droits des Mineurs
        if (preg_match('/\bANGDM\b/', $n)) {
            return ['key' => 'retraite_minier', 'label' => 'Retraite (régime minier)'];
        }

        // --- Retraite complémentaire AGIRC-ARRCO ---
        if (preg_match('/\b(?:AGIRC|ARRCO|AGERC|AG[EI]RC[-\s]ARRCO|MM[-\s]AGERC[-\s]ARRCO)\b/', $n)) {
            return ['key' => 'retraite_complementaire', 'label' => 'Retraite complémentaire'];
        }

        // --- Retraite de base (CNAV, CARSAT, CGSS, MSA...) ---
        if (preg_match('/\b(?:CNAV|CARSAT|CGSS|MSA|CRAM|CNRACL|RETRAITE\s+DE\s+BASE|PENSION\s+RETRAITE)\b/', $n)
            || preg_match('/\bCNAV\b/', $n)) {
            return ['key' => 'retraite_base', 'label' => 'Retraite de base'];
        }

        // --- Retraite fonctionnaire ---
        if (preg_match('/\b(?:SRE|FSPOEIE|RAFP|TRESORERIE\s+GENERALE|SERVICE\s+DES\s+RETRAITES\s+DE\s+LETAT)\b/', $n)) {
            return ['key' => 'retraite_fonctionnaire', 'label' => 'Retraite fonction publique'];
        }

        // Generic pension keyword fallback
        if (preg_match('/\b(?:RETRAITE|PENSION|RENTE\s+VIAGERE|RENTE\s+RETRAITE)\b/', $n)) {
            return ['key' => 'retraite', 'label' => 'Retraite'];
        }

        // --- Salaire ---
        if (preg_match('/\b(?:SALAIRE|PAIE|PAIES|TRAITEMENT|BULLETIN\s+DE\s+PAIE)\b/', $n)) {
            return ['key' => 'salaire', 'label' => 'Salaire'];
        }

        // --- Prestations sociales ---
        if (preg_match('/\b(?:CAF|CPAM|SECU|SECURITE\s+SOCIALE|ALLOCATION|INDEMNITES|POLE\s+EMPLOI|FRANCE\s+TRAVAIL|RSA|AAH|APL)\b/', $n)) {
            return ['key' => 'prestations_sociales', 'label' => 'Prestations sociales'];
        }

        // --- Loyer percçu ---
        if (preg_match('/\b(?:LOYER|LOCATION|LOCATAIRE|QUITTANCE)\b/', $n)) {
            return ['key' => 'loyer', 'label' => 'Loyer perçu'];
        }

        // --- Remboursement assurance ---
        if (preg_match('/\b(?:SINISTRE|INDEMNIT[EÉ]\s+SINISTRE|REMB\s+SINISTRE|REGLEMENT\s+SINISTRE|REGL\s+SINISTRE)\b/', $n)) {
            return ['key' => 'assurance_sinistre', 'label' => 'Règlement sinistre'];
        }

        // --- CANSS (Caisse autonome de sécurité sociale minière) ---
        if (preg_match('/\bCANSS\b/', $n)) {
            return ['key' => 'securite_sociale_miniere', 'label' => 'CANSS (Sécu. sociale minière)'];
        }

        return null;
    }

    private function computeMedian(Collection $values): float
    {
        $sorted = $values->map(fn ($v) => (float) $v)->sort()->values();
        $count = $sorted->count();

        if ($count === 0) {
            return 0.0;
        }

        $middle = intdiv($count, 2);
        if ($count % 2 === 1) {
            return (float) $sorted->get($middle);
        }

        return ((float) $sorted->get($middle - 1) + (float) $sorted->get($middle)) / 2;
    }

    /**
     * @return array{key:string,label:string}
     */
    private function resolveBeneficiaryIdentity(?object $transaction): array
    {
        if (!$transaction) {
            return ['key' => 'INCONNU', 'label' => 'Inconnu'];
        }

        $raw = trim((string) ($transaction->destination ?: $transaction->origin ?: $transaction->normalized_label ?: $transaction->label));
        if ($raw === '') {
            return ['key' => 'INCONNU', 'label' => 'Inconnu'];
        }

        if ($this->isInternalTransferLabel($raw)) {
            return ['key' => 'XFER_INTERNE_GIORDANO', 'label' => 'Transfert inter-comptes GIORDANO (hors périmètre)'];
        }

        $normalized = Normalization::normalizeLabel($raw);

        // Manual override takes priority over all automatic rules.
        if (isset($this->activeOverrides) && $this->activeOverrides->has($normalized)) {
            $override = $this->activeOverrides->get($normalized);
            return [
                'key'   => (string) $override->identity_key,
                'label' => (string) $override->identity_label,
            ];
        }

        $strictIdentity = $this->resolveStrictGiordanoIdentity($normalized);

        if ($strictIdentity !== null) {
            return $strictIdentity;
        }

        $tokens = preg_split('/\s+/u', $normalized) ?: [];
        $tokens = array_values(array_filter($tokens, function ($token) {
            if ($token === '' || mb_strlen($token) < 3) {
                return false;
            }

            $stopwords = [
                'VIREMENT', 'SEPA', 'EMIS', 'EMS', 'EM', 'RECU', 'MOT', 'MOTIF', 'BEN', 'IBEN', 'REF', 'REFDO', 'REFBEN', 'CHEQUE', 'CCP',
                'COMPTE', 'FRAIS', 'CARTE', 'RETRAIT', 'DAB', 'PRELEVEMENT', 'PRLV', 'DE', 'DU', 'DES', 'POUR', 'LE', 'LA', 'LES',
                'MADAME', 'MONSIEUR', 'MME', 'MR', 'M', 'MM',
            ];

            return !in_array($token, $stopwords, true);
        }));

        $aliasCluster = $this->matchBeneficiaryAliasCluster($normalized, $tokens);
        if ($aliasCluster !== null) {
            return [
                'key' => (string) ($aliasCluster['key'] ?? 'INCONNU'),
                'label' => (string) ($aliasCluster['label'] ?? Normalization::cleanLabel($raw)),
            ];
        }

        if ($tokens === []) {
            $clean = Normalization::cleanLabel($raw);

            return [
                'key' => 'RAW_'.md5($clean),
                'label' => $clean,
            ];
        }

        sort($tokens);
        $identityKey = 'BEN_'.implode('_', array_slice($tokens, 0, 5));

        return [
            'key' => $identityKey,
            'label' => Normalization::cleanLabel($raw),
        ];
    }

    /**
     * @return array{key:string,label:string}|null
     */
    private function resolveStrictGiordanoIdentity(string $normalized): ?array
    {
        if ($normalized === '') {
            return null;
        }

        $hasGiordano = preg_match('/\bGI?ORDANO\b/u', $normalized) === 1;
        $hasNovak = preg_match('/\bNOVAK\b/u', $normalized) === 1;
        $hasLiliane = preg_match('/\bLILIANE\b/u', $normalized) === 1;
        $hasAnthonyNamed = preg_match('/\b(?:GI?ORDANO\s+ANTHONY|ANTHONY\s+GI?ORDANO)\b/u', $normalized) === 1
            || ($hasGiordano && preg_match('/\bANTHONY\b/u', $normalized) === 1
                && preg_match('/\b(?:MME|MADAME|LILIANE|NOVAK)\b/u', $normalized) === 0);
        $hasEmilieNamed = preg_match('/\b(?:GI?ORDANO\s+EMILIE|EMILIE\s+GI?ORDANO|GI?ORDANO\s+EMILE|EMILE\s+GI?ORDANO)\b/u', $normalized) === 1;
        $hasChristianVariant =
            preg_match('/\b(?:CHRISTIAN|CHRISTAN|CHRESTIAN|CHRESTAN|CHRSTIAN|CHRSTAN|VHRISTIAN|VHRSTIAN)\b/u', $normalized) === 1
            || preg_match('/\bCHR[:\'"\s]*ST[:\'"\s]*AN\b/u', $normalized) === 1
            || preg_match('/\bCHR(?:I|E)?ST(?:I|E)?AN\b/u', $normalized) === 1
            || preg_match('/\bCHR[:\'"\s]*STE?AN\b/u', $normalized) === 1
            || preg_match('/\bCHRIST\b/u', $normalized) === 1
            || preg_match('/\bCHRSTE?\b/u', $normalized) === 1
            || preg_match('/\bCRYST\b/u', $normalized) === 1
            || preg_match('/\bCHR[A-Z]{0,2}ST[A-Z]{0,3}\b/u', $normalized) === 1;
        $hasFemaleTitle = preg_match('/\b(MME|MADAME)\b/u', $normalized) === 1;
        $hasMaleTitle = preg_match('/\b(MR|MONSIEUR)\b/u', $normalized) === 1;

        $hasJointMarker =
            preg_match('/\bM\s*OU\s*MME\b/u', $normalized) === 1
            || preg_match('/\bM\s*ET\s*MME\b/u', $normalized) === 1
            || preg_match('/\bM\b.{0,24}\bO[UÙ]\b.{0,12}\bMME\b/u', $normalized) === 1
            || preg_match('/\bMME\b.{0,24}\bO[UÙ]\b.{0,12}\bM\b/u', $normalized) === 1
            || preg_match('/\b(MR|MONSIEUR)\b.*\b(MME|MADAME)\b/u', $normalized) === 1
            || preg_match('/\b(MME|MADAME)\b.*\b(MR|MONSIEUR)\b/u', $normalized) === 1
            || ($hasChristianVariant && $hasFemaleTitle && $hasGiordano);

        $hasFemaleBeneficiaryContext =
            preg_match('/\b(?:BEN|IBEN|BENEF|BENEFICIAIRE|DEST|DESTINATAIRE|VERS)\b.{0,90}\b(?:MME|MADAME|LILIANE|NOVAK)\b/u', $normalized) === 1
            || preg_match('/\b(?:MME|MADAME|LILIANE|NOVAK)\b.{0,90}\b(?:BEN|IBEN|BENEF|BENEFICIAIRE|DEST|DESTINATAIRE|VERS)\b/u', $normalized) === 1;

        if ($hasAnthonyNamed) {
            return [
                'key' => 'PERSONNE_ANTHONY_GIORDANO',
                'label' => 'M. Anthony GIORDANO',
            ];
        }

        if ($hasEmilieNamed) {
            return [
                'key' => 'PERSONNE_EMILIE_GIORDANO',
                'label' => 'Mme Emilie GIORDANO',
            ];
        }

        if ($hasJointMarker && $hasGiordano) {
            return [
                'key' => 'COMPTE_COMMUN_GIORDANO',
                'label' => 'M. ou Mme GIORDANO (compte commun)',
            ];
        }

        if ($hasChristianVariant && ($hasNovak || $hasLiliane || ($hasFemaleTitle && $hasGiordano)) && $hasFemaleBeneficiaryContext) {
            return [
                'key' => 'PERSONNE_LILIANE_GIORDANO_NOVAK',
                'label' => 'Mme Liliane GIORDANO / NOVAK',
            ];
        }

        if (($hasMaleTitle && $hasGiordano) || ($hasChristianVariant && $hasGiordano)) {
            return [
                'key' => 'PERSONNE_M_GIORDANO',
                'label' => 'M. GIORDANO Christian',
            ];
        }

        if (($hasFemaleTitle && ($hasGiordano || $hasNovak)) || $hasLiliane || $hasNovak) {
            return [
                'key' => 'PERSONNE_LILIANE_GIORDANO_NOVAK',
                'label' => 'Mme Liliane GIORDANO / NOVAK',
            ];
        }

        return null;
    }

    /**
     * Identify the source (counterparty) of an incoming credit transaction.
     * Prioritises known institutional patterns before delegating to resolveBeneficiaryIdentity.
     *
     * @return array{key:string,label:string}
     */
    private function resolveSourceIdentity(?object $transaction): array
    {
        if (!$transaction) {
            return ['key' => 'INCONNU', 'label' => 'Inconnu'];
        }

        $raw = trim((string) ($transaction->origin ?: $transaction->destination ?: $transaction->normalized_label ?: $transaction->label));
        if ($raw === '') {
            return ['key' => 'INCONNU', 'label' => 'Inconnu'];
        }

        if ($this->isInternalTransferLabel($raw)) {
            return ['key' => 'XFER_INTERNE_GIORDANO', 'label' => 'Transfert inter-comptes GIORDANO (hors périmètre)'];
        }

        $n = Normalization::normalizeLabel($raw);

        // Known institutional / income sources
        $institutions = [
            ['patterns' => ['ANGDM', 'CHARBONNAGE'],                            'key' => 'INST_ANGDM',      'label' => 'ANGDM (Retraite régime minier)'],
            ['patterns' => ['CANSS MINES', 'CANSS MENES', 'CANSS'],             'key' => 'INST_CANSS',      'label' => 'CANSS (Sécurité sociale minière)'],
            ['patterns' => ['CNAV', 'CARSAT', 'ASSURANCE RETRAITE'],            'key' => 'INST_CNAV',       'label' => 'CNAV / CARSAT (Retraite de base)'],
            ['patterns' => ['AGIRC', 'ARRCO', 'AGRC ARRCO', 'AGERC ARRCO', 'MALAKOFF', 'HUMANIS'], 'key' => 'INST_AGIRC', 'label' => 'AGIRC-ARRCO (Retraite complémentaire)'],
            ['patterns' => ['CAF ', 'CAISSE ALLOC', 'CAISSE FAMILLE'],          'key' => 'INST_CAF',        'label' => 'CAF (Prestations familiales)'],
            ['patterns' => ['CPAM', 'SECURITE SOCIALE', 'ASSUR MAL'],           'key' => 'INST_CPAM',       'label' => 'CPAM (Sécurité sociale)'],
            ['patterns' => ['POLE EMPLOI', 'FRANCE TRAVAIL', 'UNEDIC'],         'key' => 'INST_PE',         'label' => 'Pôle Emploi / France Travail'],
            ['patterns' => ['TRESOR PUBLIC', 'DGFIP', 'REMBOURSEMENT IMPOT'],   'key' => 'INST_FISC',       'label' => 'Trésor public / Impôts'],
            // MATMUT = Mutuelle d'Assurance des Travailleurs Mutualistes.
            // On capture aussi le nom complet développé (libellé OCR fréquent).
            ['patterns' => ['MATMUT', 'TRAVAILLEUR MUTUALISTE'],                 'key' => 'INST_MATMUT',     'label' => 'Assurance / MATMUT'],
            ['patterns' => ['MAIF', 'AXA', 'ALLIANZ', 'GENERALI', 'GROUPAMA', 'MMA '], 'key' => 'INST_ASSURANCE', 'label' => 'Assurance (sinistre / RC)'],
            ['patterns' => ['REMISE CHEQUE', 'REMISE CH ', 'BORDEREAU'],        'key' => 'METH_REMISE_CHQ', 'label' => 'Remise de chèques'],
            ['patterns' => ['VIREMENT SOLDE', 'SOLDE CPTE', 'SOLDE DE VOTRE CPTE'], 'key' => 'METH_VIR_INTERNE', 'label' => 'Virement interne (solde)'],
        ];

        foreach ($institutions as $inst) {
            foreach ($inst['patterns'] as $pattern) {
                if (str_contains($n, $pattern)) {
                    return ['key' => $inst['key'], 'label' => $inst['label']];
                }
            }
        }

        // Fall back to the same identity resolution used for outgoing (handles GIORDANO/NOVAK, tokenisation)
        return $this->resolveBeneficiaryIdentity($transaction);
    }

    private function isInternalTransferLabel(string $raw): bool
    {
        $normalized = Normalization::normalizeLabel($raw);
        if ($normalized === '') {
            return false;
        }

        if (preg_match('/\bCPTE\s*A\s*CPTE\b/u', $normalized) === 1) {
            return true;
        }

        if (preg_match('/\bCOMPTE\s*A\s*COMPTE\b/u', $normalized) === 1) {
            return true;
        }

        if (preg_match('/\b(?:VIREMENT|VIR)\s+INTERNE\b/u', $normalized) === 1) {
            return true;
        }

        if (preg_match('/\b(?:DE\s+VOTRE\s+CPTE|VOS\s+COMPTES|VOTRE\s+CPTE|SOLDE\s+CPTE)\b/u', $normalized) === 1) {
            return true;
        }

        if (preg_match('/\b(?:RECU\s*DE|DE)\s+M\s+CHR\s*ST\s*AN\s+GIORDANO\b/u', $normalized) === 1) {
            return true;
        }

        return false;
    }

    /**
     * @return array{total:int,classified:int,ambiguous:int,examples:array<int,string>}
     */
    private function buildTransferBeneficiaryPrecheck(CaseFile $case, ?Carbon $rangeStart = null, ?Carbon $rangeEnd = null): array
    {
        $accountIds = $case->bankAccounts()->pluck('id');

        $query = Transaction::query()
            ->whereIn('bank_account_id', $accountIds)
            ->where('type', 'debit')
            ->where(function (Builder $q) {
                $q->where('kind', 'transfer')
                    ->orWhere('label', 'ilike', '%VIR%')
                    ->orWhere('label', 'ilike', '%VER%')
                    ->orWhere('normalized_label', 'ilike', '%VIR%')
                    ->orWhere('normalized_label', 'ilike', '%VER%');
            })
            ->orderBy('date')
            ->orderBy('id');

        if ($rangeStart !== null) {
            $query->whereDate('date', '>=', $rangeStart->toDateString());
        }
        if ($rangeEnd !== null) {
            $query->whereDate('date', '<=', $rangeEnd->toDateString());
        }

        $rows = $query->get(['id', 'date', 'label', 'normalized_label', 'origin', 'destination', 'kind', 'type']);

        $total = $rows->count();
        $ambiguous = 0;
        $examples = [];

        foreach ($rows as $tx) {
            $identity = $this->resolveBeneficiaryIdentity($tx);
            $key = (string) ($identity['key'] ?? 'INCONNU');

            $isAmbiguous =
                $key === 'INCONNU'
                || $key === 'PERSONNE_GIORDANO_NOVAK'
                || str_starts_with($key, 'BEN_')
                || str_starts_with($key, 'RAW_');

            if ($isAmbiguous) {
                $ambiguous++;
                if (count($examples) < 5) {
                    $examples[] = optional($tx->date)->format('Y-m-d').' '.$tx->label;
                }
            }
        }

        return [
            'total' => $total,
            'classified' => max(0, $total - $ambiguous),
            'ambiguous' => $ambiguous,
            'examples' => $examples,
        ];
    }

    /**
     * @param array<int,string> $tokens
     * @return array{key:string,label:string,query?:string}|null
     */
    private function matchBeneficiaryAliasCluster(string $normalized, array $tokens): ?array
    {
        $clusters = config('analytica.beneficiary_alias_clusters', []);
        if (!is_array($clusters)) {
            return null;
        }

        foreach ($clusters as $cluster) {
            if (!is_array($cluster)) {
                continue;
            }

            $clusterTokens = collect((array) ($cluster['tokens'] ?? []))
                ->map(fn ($value) => Normalization::normalizeLabel((string) $value))
                ->filter(fn ($value) => $value !== '')
                ->unique()
                ->values()
                ->all();

            if ($clusterTokens === []) {
                continue;
            }

            $matches = 0;
            foreach ($clusterTokens as $clusterToken) {
                if (in_array($clusterToken, $tokens, true) || str_contains($normalized, $clusterToken)) {
                    $matches++;
                }
            }

            $minMatch = max(1, (int) ($cluster['min_match'] ?? 1));
            if ($matches < $minMatch) {
                continue;
            }

            return [
                'key' => (string) ($cluster['key'] ?? 'INCONNU'),
                'label' => (string) ($cluster['label'] ?? 'Bénéficiaire rapproché'),
                'query' => (string) ($cluster['query'] ?? ''),
            ];
        }

        return null;
    }

    /**
     * @return array<string,string>
     */
    private function buildTransactionFilters(Request $request): array
    {
        return [
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'min_amount' => (string) $request->query('min_amount', ''),
            'max_amount' => (string) $request->query('max_amount', ''),
            'type' => (string) $request->query('type', ''),
            'kind' => (string) $request->query('kind', ''),
            'q' => (string) $request->query('q', ''),
            'score_min' => (string) $request->query('score_min', ''),
            'bank_name' => (string) $request->query('bank_name', ''),
            'account_profile' => (string) $request->query('account_profile', 'joint'),
            'bank_account_id' => (string) $request->query('bank_account_id', ''),
        ];
    }

    /**
     * @param array<string,string> $filters
     */
    private function applyTransactionFilters(Builder $txBase, array $filters): void
    {
        if ($filters['date_from'] !== '') {
            $txBase->whereDate('date', '>=', $filters['date_from']);
        }
        if ($filters['date_to'] !== '') {
            $txBase->whereDate('date', '<=', $filters['date_to']);
        }
        if ($filters['type'] === 'debit') {
            $txBase->where('type', 'debit');
        } elseif ($filters['type'] === 'credit') {
            $txBase->where('type', 'credit');
        }
        if ($filters['kind'] !== '') {
            $txBase->where('kind', $filters['kind']);
        }
        if ($filters['q'] !== '') {
            $term = $filters['q'];
            $txBase->where(function ($query) use ($term) {
                $query
                    ->where('label', 'ilike', '%'.$term.'%')
                    ->orWhere('motif', 'ilike', '%'.$term.'%')
                    ->orWhere('origin', 'ilike', '%'.$term.'%')
                    ->orWhere('destination', 'ilike', '%'.$term.'%')
                    ->orWhere('normalized_label', 'ilike', '%'.mb_strtoupper($term).'%')
                    ->orWhere('cheque_number', 'ilike', '%'.$term.'%');
            });
        }
        if ($filters['score_min'] !== '' && is_numeric($filters['score_min'])) {
            $txBase->where('anomaly_score', '>=', (float) $filters['score_min']);
        }
        if ($filters['min_amount'] !== '' && is_numeric($filters['min_amount'])) {
            $txBase->whereRaw('abs(amount) >= ?', [(float) $filters['min_amount']]);
        }
        if ($filters['max_amount'] !== '' && is_numeric($filters['max_amount'])) {
            $txBase->whereRaw('abs(amount) <= ?', [(float) $filters['max_amount']]);
        }
    }

    /**
     * @return array{full:string,short:string,truncated:bool}
     */
    private function buildDisplayLabel(string $label, int $max = 120): array
    {
        $clean = $this->stripLegalMentions($label);
        $clean = preg_replace('/\s+/u', ' ', trim($clean)) ?? trim($clean);

        if ($clean === '') {
            return [
                'full' => '—',
                'short' => '—',
                'truncated' => false,
            ];
        }

        $isTruncated = mb_strlen($clean) > $max;

        return [
            'full' => $clean,
            'short' => $isTruncated ? mb_substr($clean, 0, $max).'…' : $clean,
            'truncated' => $isTruncated,
        ];
    }

    private function stripLegalMentions(string $label): string
    {
        $patterns = [
            '/BNP\s+PARIBAS\s+SA\s+AU\s+CAPITAL.*$/iu',
            '/SERVICE\s+CLIENT.*$/iu',
            '/\bRCS\b.*$/iu',
            '/\bORIAS\b.*$/iu',
            '/\bTAEG\b.*$/iu',
        ];

        $clean = $label;
        foreach ($patterns as $pattern) {
            $clean = preg_replace($pattern, ' ', $clean) ?? $clean;
        }

        return $clean;
    }
}
