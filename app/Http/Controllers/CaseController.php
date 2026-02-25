<?php

namespace App\Http\Controllers;

use App\Models\CaseFile;
use App\Models\Transaction;
use App\Services\AnalysisEngine;
use App\Services\Normalization;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CaseController extends Controller
{
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

        $filters = [
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'min_amount' => (string) $request->query('min_amount', ''),
            'max_amount' => (string) $request->query('max_amount', ''),
            'type' => (string) $request->query('type', ''),
            'kind' => (string) $request->query('kind', ''),
            'q' => (string) $request->query('q', ''),
            'score_min' => (string) $request->query('score_min', ''),
            'bank_name' => (string) $request->query('bank_name', ''),
            'account_profile' => (string) $request->query('account_profile', ''),
            'bank_account_id' => (string) $request->query('bank_account_id', ''),
        ];

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
            $selectedAccounts = $selectedAccounts->filter(function ($account) use ($accountProfileById, $filters) {
                return ($accountProfileById[$account->getKey()] ?? 'personal') === $filters['account_profile'];
            })->values();
        }

        if ($filters['bank_account_id'] !== '' && ctype_digit($filters['bank_account_id'])) {
            $selectedAccounts = $selectedAccounts->where('id', (int) $filters['bank_account_id']);
        }

        $accountIds = $selectedAccounts->pluck('id');

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

        $stats = [
            'global_score' => $case->global_score,
            'total_accounts' => $selectedAccounts->count(),
            'total_transactions' => $totalTransactions,
            'total_flagged' => $totalFlagged,
            'total_flagged_amount' => round($totalFlaggedAmount, 2),
            'top_beneficiaries' => $topBeneficiaries,
            'timeline' => $timeline,
        ];

        $txBase = Transaction::query()->whereIn('bank_account_id', $accountIds);

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

        $totalDebit = (float) (clone $txBase)->where('type', 'debit')->sum(DB::raw('abs(amount)'));
        $totalCredit = (float) (clone $txBase)->where('type', 'credit')->sum(DB::raw('abs(amount)'));
        $net = $totalCredit - $totalDebit;

        $transactions = (clone $txBase)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        $reports = $case->reports->sortByDesc('generated_at')->take(20);
        $latestPdfReport = $reports->first(fn ($report) => str_contains((string) ($report->mime_type ?? ''), 'pdf'));
        $latestAnalysisResult = $case->analysisResults()->orderByDesc('generated_at')->orderByDesc('id')->first();
        $lastAi = (array) $request->session()->get('cases.'.$case->getKey().'.last_ai', []);

        $allTx = Transaction::query()
            ->whereIn('bank_account_id', $accountIds)
            ->orderBy('date')
            ->get(['id', 'bank_account_id', 'date', 'amount', 'type', 'label', 'normalized_label']);

        $exceptionalThreshold = 20000.0;
        $exceptionalTransactions = $allTx
            ->filter(fn ($t) => abs((float) $t->amount) >= $exceptionalThreshold)
            ->sortByDesc(fn ($t) => abs((float) $t->amount))
            ->take(20)
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
            $periodStart = Carbon::parse($allTxWithDate->min('date'))->startOfMonth();
            $periodEnd = Carbon::parse($allTxWithDate->max('date'))->startOfMonth();

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
            ->values();

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
            'exceptional_threshold' => $exceptionalThreshold,
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
                'monthly_totals' => $monthlyTotals,
                'monthly_max' => $monthlyMax,
                'sensitive_stats' => $sensitiveStats,
                'top_spikes' => $topSpikes,
                'spike_threshold' => round((float) $spikeThreshold, 2),
            ],
        ]);
    }

    public function analyze(Request $request, CaseFile $case, AnalysisEngine $engine)
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

        return redirect()->route('cases.show', $case)->with('status', 'Analyse générée le '.$generatedAt.' ('.$scopeLabel.', score '.$result->global_score.', anomalies '.$result->total_flagged.').');
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
}
