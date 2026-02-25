<x-app-layout>
    <x-slot name="header">
        @php($statusLabel = match((string) ($case->status ?? '')) {
            'draft' => 'Brouillon',
            'in_progress' => 'En cours',
            'completed' => 'Terminé',
            'archived' => 'Archivé',
            default => (string) ($case->status ?? '—'),
        })
        @php($scoreValue = is_null($case->global_score) ? null : (int) $case->global_score)
        @php($scoreLevelLabel = is_null($scoreValue) ? 'Non calculé' : ($scoreValue >= 60 ? 'Fortement atypique' : ($scoreValue >= 30 ? 'Atypique' : 'Normal')))
        @php($scoreLevelClass = is_null($scoreValue) ? 'bg-gray-100 text-gray-700 border-gray-200' : ($scoreValue >= 60 ? 'bg-red-50 text-red-700 border-red-200' : ($scoreValue >= 30 ? 'bg-yellow-50 text-yellow-700 border-yellow-200' : 'bg-green-50 text-green-700 border-green-200')))
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-3xl text-gray-800 leading-tight">
                    {{ $case->title }}
                </h2>
                <p class="mt-1 text-base text-gray-600">Statut: {{ $statusLabel }} · Score: {{ $case->global_score ?? '—' }}</p>
                <div class="mt-2 inline-flex items-center px-3 py-1 rounded-full border text-xs font-semibold {{ $scoreLevelClass }}">
                    Niveau: {{ $scoreLevelLabel }}
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-[112rem] mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-4 text-sm text-green-700">
                        {{ session('status') }}
                    </div>
                </div>
            @endif

            @if (session('analysis_error'))
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-4 text-sm text-red-700">
                        {{ session('analysis_error') }}
                    </div>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="border rounded-lg p-4 bg-gray-50">
                        <div class="text-sm font-semibold text-gray-700">Actions rapides</div>
                        <div class="mt-3 flex flex-wrap gap-3 items-end">
                            <form method="POST" action="{{ route('cases.analyze', $case) }}">
                                @csrf
                                <input type="hidden" name="analysis_scope" value="all" />
                                <button type="submit" class="inline-flex items-center px-5 py-3 bg-green-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">{{ __('Analyser tout l’historique') }}</button>
                            </form>

                            <form method="POST" action="{{ route('cases.analyze', $case) }}" class="grid grid-cols-1 sm:grid-cols-3 gap-2 items-end">
                                @csrf
                                <input type="hidden" name="analysis_scope" value="period" />
                                <div>
                                    <x-input-label for="analysis_from_action" :value="__('Du')" />
                                    <x-text-input id="analysis_from_action" name="analysis_from" type="date" class="mt-1 block w-full" :value="old('analysis_from', optional($case->analysis_period_start)->format('Y-m-d'))" />
                                </div>
                                <div>
                                    <x-input-label for="analysis_to_action" :value="__('Au')" />
                                    <x-text-input id="analysis_to_action" name="analysis_to" type="date" class="mt-1 block w-full" :value="old('analysis_to', optional($case->analysis_period_end)->format('Y-m-d'))" />
                                </div>
                                <button type="submit" class="inline-flex items-center px-5 py-3 bg-green-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">{{ __('Analyser période') }}</button>
                            </form>

                            <form method="POST" action="{{ route('reports.generate', $case) }}">
                                @csrf
                                <input type="hidden" name="format" value="pdf" />
                                <button type="submit" class="inline-flex items-center px-5 py-3 bg-green-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">{{ __('Générer PDF') }}</button>
                            </form>

                            <form method="POST" action="{{ route('reports.generate', $case) }}">
                                @csrf
                                <input type="hidden" name="format" value="xlsx" />
                                <button type="submit" class="inline-flex items-center px-5 py-3 bg-green-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">{{ __('Générer Excel') }}</button>
                            </form>

                            @if (!empty($latest_pdf_report))
                                <a href="{{ route('reports.download', $latest_pdf_report) }}" class="inline-flex items-center px-5 py-3 bg-green-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">{{ __('Télécharger dernier PDF') }}</a>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 border rounded-lg p-4 bg-white">
                        <div class="text-sm font-semibold text-gray-700">Informations dossier</div>
                        <form method="POST" action="{{ route('cases.update-details', $case) }}" class="mt-3 grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
                            @csrf
                            @method('PATCH')

                            <div>
                                <x-input-label for="deceased_name" :value="__('Nom du défunt')" />
                                <x-text-input id="deceased_name" name="deceased_name" type="text" class="mt-1 block w-full" :value="old('deceased_name', $case->deceased_name)" />
                                <x-input-error class="mt-1" :messages="$errors->get('deceased_name')" />
                            </div>

                            <div>
                                <x-input-label for="death_date" :value="__('Date de décès')" />
                                <x-text-input id="death_date" name="death_date" type="date" class="mt-1 block w-full" :value="old('death_date', optional($case->death_date)->format('Y-m-d'))" />
                                <x-input-error class="mt-1" :messages="$errors->get('death_date')" />
                            </div>

                            <div>
                                <x-input-label for="analysis_period_start" :value="__('Début période')" />
                                <x-text-input id="analysis_period_start" name="analysis_period_start" type="date" class="mt-1 block w-full" :value="old('analysis_period_start', optional($case->analysis_period_start)->format('Y-m-d'))" />
                                <x-input-error class="mt-1" :messages="$errors->get('analysis_period_start')" />
                            </div>

                            <div>
                                <x-input-label for="analysis_period_end" :value="__('Fin période')" />
                                <x-text-input id="analysis_period_end" name="analysis_period_end" type="date" class="mt-1 block w-full" :value="old('analysis_period_end', optional($case->analysis_period_end)->format('Y-m-d'))" />
                                <x-input-error class="mt-1" :messages="$errors->get('analysis_period_end')" />
                            </div>

                            <div class="md:col-span-4">
                                <x-primary-button>
                                    Enregistrer informations
                                </x-primary-button>
                            </div>
                        </form>
                    </div>

                    @php($latestAnalysis = $latest_analysis_result ?? null)
                    @php($lastAi = $last_ai ?? [])
                    @php($ai = session('ai_result', $lastAi['result'] ?? null))
                    @php($aiError = session('ai_error', $lastAi['error'] ?? null))

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div class="border rounded-md p-4 bg-green-50 border-green-100">
                            <div class="font-semibold text-gray-800">Suivi analyse dossier</div>
                            @if ($latestAnalysis)
                                <div class="mt-2 text-gray-700">Dernière exécution: <span class="font-medium whitespace-nowrap">{{ optional($latestAnalysis->generated_at)->format('d/m/Y H:i') }}</span></div>
                                <div class="mt-1 text-gray-700">Score: <span class="font-medium">{{ $latestAnalysis->global_score ?? '—' }}</span> · Anomalies: <span class="font-medium">{{ $latestAnalysis->total_flagged ?? '—' }}</span> / {{ $latestAnalysis->total_transactions ?? '—' }}</div>
                            @else
                                <div class="mt-2 text-gray-600">Aucune analyse dossier enregistrée pour le moment. Clique sur <span class="font-medium">Analyser tout l’historique</span>.</div>
                            @endif
                        </div>

                        <div class="border rounded-md p-4 bg-gray-50">
                            <div class="font-semibold text-gray-800">Suivi Assistant IA</div>
                            @if (!empty($lastAi['ran_at']))
                                <div class="mt-2 text-gray-700">Dernière demande: <span class="font-medium whitespace-nowrap">{{ \Carbon\Carbon::parse($lastAi['ran_at'])->format('d/m/Y H:i') }}</span></div>
                                <div class="mt-1 text-gray-700">Question: <span class="font-medium">{{ $lastAi['prompt'] !== '' ? $lastAi['prompt'] : 'demande par défaut' }}</span></div>
                            @else
                                <div class="mt-2 text-gray-600">Aucune demande IA envoyée pour ce dossier.</div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 border rounded-md p-4 bg-gray-50 text-sm">
                        <div class="font-semibold text-gray-800">Contrôle multi-années & titulaires</div>
                        <div class="mt-2 text-gray-700">
                            Période couverte par les transactions importées:
                            <span class="font-medium">{{ $date_coverage['from'] ?? '—' }}</span>
                            →
                            <span class="font-medium">{{ $date_coverage['to'] ?? '—' }}</span>
                        </div>

                        @if (($account_insights ?? collect())->count() === 0)
                            <div class="mt-2 text-gray-600">Aucun compte détecté.</div>
                        @else
                            <div class="mt-3 space-y-3">
                                @foreach (($account_insights ?? collect()) as $insight)
                                    <div class="border rounded-md p-3 bg-white">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-medium">{{ $insight['bank_name'] ?? 'Compte' }}</span>
                                            <span class="text-xs px-2 py-0.5 rounded border {{ !empty($insight['is_joint']) ? 'bg-yellow-50 text-yellow-700 border-yellow-200' : 'bg-green-50 text-green-700 border-green-200' }}">
                                                {{ !empty($insight['is_joint']) ? 'Compte commun probable' : 'Compte personnel probable' }}
                                            </span>
                                            @if (!empty($insight['has_identifier_change']))
                                                <span class="text-xs px-2 py-0.5 rounded border bg-red-50 text-red-700 border-red-200">Changement de numéro détecté</span>
                                            @endif
                                        </div>
                                        <div class="mt-2 text-xs text-gray-600">
                                            Période compte: {{ $insight['period_start'] ?? '—' }} → {{ $insight['period_end'] ?? '—' }} · Lignes: {{ $insight['transactions_count'] ?? 0 }}
                                        </div>
                                        <div class="mt-1 text-xs text-gray-600">
                                            Numéros détectés:
                                            @if (empty($insight['identifiers']))
                                                <span class="font-medium">—</span>
                                            @else
                                                @foreach (($insight['identifiers'] ?? []) as $id)
                                                    <span class="inline-flex items-center px-2 py-0.5 ml-1 mt-1 rounded bg-gray-50 border border-gray-200 whitespace-nowrap">{{ $id }}</span>
                                                @endforeach
                                            @endif
                                        </div>
                                        <div class="mt-1 text-xs text-gray-600">
                                            Mentions titulaire(s):
                                            @if (empty($insight['holders']))
                                                <span class="font-medium">—</span>
                                            @else
                                                @foreach (($insight['holders'] ?? []) as $holder)
                                                    <span class="inline-flex items-center px-2 py-0.5 ml-1 mt-1 rounded bg-gray-50 border border-gray-200">{{ $holder }}</span>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-4 pt-3 border-t border-gray-200">
                            <div class="font-medium text-gray-800">Changements détectés (chronologie)</div>
                            @if (($account_change_events ?? collect())->count() === 0)
                                <div class="mt-2 text-gray-600">Aucun changement notable détecté sur numéro de compte / titulaires.</div>
                            @else
                                <ul class="mt-2 space-y-1 text-xs text-gray-700">
                                    @foreach (($account_change_events ?? collect()) as $event)
                                        <li>
                                            <span class="font-medium whitespace-nowrap">{{ $event['date'] ?? '—' }}</span>
                                            · <span class="font-medium">{{ $event['bank_name'] ?? 'Compte' }}</span>
                                            · {{ $event['message'] ?? 'Changement détecté' }}
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 border rounded-md p-4 bg-red-50 border-red-100">
                        <div class="font-semibold text-gray-800">Montants exceptionnels (≥ {{ number_format((float)($exceptional_threshold ?? 20000), 0, ',', ' ') }} €)</div>
                        <div class="mt-1 text-xs text-gray-600">Vérification rapide des mouvements majeurs (ex: vente terrain 180 000€).</div>
                        @if (($exceptional_transactions ?? collect())->count() === 0)
                            <div class="mt-2 text-sm text-gray-600">Aucun mouvement exceptionnel trouvé sur les filtres actuels.</div>
                        @else
                            <div class="mt-3 overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-600">
                                            <th class="py-2 pr-4">Date</th>
                                            <th class="py-2 pr-4">Compte</th>
                                            <th class="py-2 pr-4">Libellé</th>
                                            <th class="py-2 pr-4">Sens</th>
                                            <th class="py-2 pr-4 text-right">Montant</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        @foreach (($exceptional_transactions ?? collect()) as $tx)
                                            @php($account = $case->bankAccounts->firstWhere('id', $tx->bank_account_id))
                                            <tr>
                                                <td class="py-2 pr-4 whitespace-nowrap">{{ optional($tx->date)->format('Y-m-d') }}</td>
                                                <td class="py-2 pr-4 whitespace-nowrap">{{ $account?->bank_name ?: ('Compte #'.$tx->bank_account_id) }}</td>
                                                <td class="py-2 pr-4 max-w-md truncate" title="{{ $tx->label }}">{{ $tx->label ?: '—' }}</td>
                                                <td class="py-2 pr-4">{{ ($tx->type ?? '') === 'debit' ? 'Débit' : 'Crédit' }}</td>
                                                <td class="py-2 pr-4 text-right font-semibold whitespace-nowrap {{ ($tx->type ?? '') === 'debit' ? 'text-red-700' : 'text-green-700' }}">{{ number_format(abs((float)$tx->amount), 2, ',', ' ') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <hr class="my-6" />

                    <h3 class="font-semibold">Rapports générés</h3>
                    @if (($reports ?? collect())->count() === 0)
                        <p class="mt-2 text-sm text-gray-600">Aucun rapport généré pour le moment.</p>
                    @else
                        <div class="mt-3 overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-600">
                                        <th class="py-2 pr-4">Date</th>
                                        <th class="py-2 pr-4">Version</th>
                                        <th class="py-2 pr-4">Fichier</th>
                                        <th class="py-2 pr-4">Type</th>
                                        <th class="py-2 pr-4 text-right">Taille</th>
                                        <th class="py-2 pr-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach (($reports ?? collect()) as $report)
                                        <tr>
                                            <td class="py-2 pr-4 whitespace-nowrap">{{ optional($report->generated_at)->format('Y-m-d H:i') }}</td>
                                            <td class="py-2 pr-4">v{{ $report->version }}</td>
                                            <td class="py-2 pr-4 max-w-md truncate" title="{{ $report->original_filename ?? '' }}">{{ $report->original_filename ?? 'rapport' }}</td>
                                            <td class="py-2 pr-4">{{ $report->mime_type ?? '—' }}</td>
                                            <td class="py-2 pr-4 text-right tabular-nums whitespace-nowrap">{{ $report->size_bytes ? number_format(((float)$report->size_bytes) / 1024, 1, ',', ' ') . ' Ko' : '—' }}</td>
                                            <td class="py-2 pr-4">
                                                <a href="{{ route('reports.download', $report) }}" class="text-green-700 hover:underline">Télécharger</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <hr class="my-6" />

                    <h3 class="font-semibold">Dashboard</h3>

                    <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                        <div class="border rounded-md p-3">
                            <div class="text-gray-600">Transactions</div>
                            <div class="text-lg font-semibold">{{ $stats['total_transactions'] ?? '—' }}</div>
                        </div>
                        <div class="border rounded-md p-3">
                            <div class="text-gray-600">Anomalies</div>
                            <div class="text-lg font-semibold">{{ $stats['total_flagged'] ?? '—' }}</div>
                        </div>
                        <div class="border rounded-md p-3">
                            <div class="text-gray-600">Montant cumulé (abs.)</div>
                            <div class="text-lg font-semibold">{{ $stats['total_flagged_amount'] ?? '—' }}</div>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                        <div>
                            <div class="font-medium">Top bénéficiaires</div>
                            @if (($stats['top_beneficiaries'] ?? collect())->count() === 0)
                                <div class="text-gray-600">—</div>
                            @else
                                <ul class="mt-2 space-y-1">
                                    @foreach ($stats['top_beneficiaries'] as $label => $amount)
                                        <li class="flex justify-between gap-4">
                                            <span class="truncate">{{ $label !== '' ? $label : '—' }}</span>
                                            <span class="tabular-nums whitespace-nowrap">{{ number_format((float) $amount, 2, ',', ' ') }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        <div>
                            <div class="font-medium">Timeline (anomalies / mois)</div>
                            @if (($stats['timeline'] ?? collect())->count() === 0)
                                <div class="text-gray-600">—</div>
                            @else
                                <ul class="mt-2 space-y-1">
                                    @foreach ($stats['timeline'] as $ym => $cnt)
                                        <li class="flex justify-between gap-4">
                                            <span>{{ $ym }}</span>
                                            <span class="tabular-nums">{{ $cnt }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>

                    @php($behavioral = $behavioral ?? [])
                    @php($monthlyTotals = collect($behavioral['monthly_totals'] ?? []))
                    @php($monthlyMax = (float) ($behavioral['monthly_max'] ?? 0))
                    @php($sensitive = $behavioral['sensitive_stats'] ?? null)
                    @php($topSpikes = collect($behavioral['top_spikes'] ?? []))

                    <hr class="my-6" />

                    <h3 class="font-semibold">Habitudes & périodes sensibles</h3>

                    @if ($sensitive)
                        <div class="mt-3 border rounded-md p-4 bg-gray-50 text-sm">
                            <div class="font-medium text-gray-800">Comparatif avant décès</div>
                            <div class="mt-2 text-gray-700">
                                Fenêtre sensible: <span class="font-medium">{{ $sensitive['window_label'] }}</span> · Référence: <span class="font-medium">{{ $sensitive['baseline_label'] }}</span>
                            </div>
                            <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div class="border rounded-md p-3 bg-white">
                                    <div class="text-xs text-gray-500">Débit mensuel (fenêtre sensible)</div>
                                    <div class="text-xl font-semibold text-gray-900">{{ number_format((float)($sensitive['sensitive_monthly_debit'] ?? 0), 2, ',', ' ') }}</div>
                                </div>
                                <div class="border rounded-md p-3 bg-white">
                                    <div class="text-xs text-gray-500">Débit mensuel (référence)</div>
                                    <div class="text-xl font-semibold text-gray-900">{{ number_format((float)($sensitive['baseline_monthly_debit'] ?? 0), 2, ',', ' ') }}</div>
                                </div>
                                <div class="border rounded-md p-3 {{ (($sensitive['severity'] ?? 'neutral') === 'high') ? 'bg-red-50 border-red-200' : ((($sensitive['severity'] ?? 'neutral') === 'medium') ? 'bg-yellow-50 border-yellow-200' : 'bg-green-50 border-green-200') }}">
                                    <div class="text-xs text-gray-600">Variation vs référence</div>
                                    <div class="text-xl font-semibold {{ (($sensitive['severity'] ?? 'neutral') === 'high') ? 'text-red-700' : ((($sensitive['severity'] ?? 'neutral') === 'medium') ? 'text-yellow-700' : 'text-green-700') }}">
                                        @if (is_null($sensitive['change_pct']))
                                            n/a
                                        @else
                                            {{ ($sensitive['change_pct'] >= 0 ? '+' : '') . $sensitive['change_pct'] }}%
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="mt-4 border rounded-md p-4 bg-white">
                        <div class="font-medium text-sm">Graphique mensuel (courbes crédits vs débits)</div>
                        <div class="mt-1 text-xs text-gray-500">Ordre chronologique automatique, mois sans opérations affichés à 0 pour garder une timeline continue.</div>
                        @if ($monthlyTotals->count() === 0)
                            <div class="mt-2 text-sm text-gray-600">Pas assez de données pour afficher le graphique.</div>
                        @else
                            @php($chartRows = $monthlyTotals->values())
                            @php($chartCount = $chartRows->count())
                            @php($chartWidth = 980)
                            @php($chartHeight = 300)
                            @php($padLeft = 56)
                            @php($padRight = 18)
                            @php($padTop = 18)
                            @php($padBottom = 44)
                            @php($plotWidth = $chartWidth - $padLeft - $padRight)
                            @php($plotHeight = $chartHeight - $padTop - $padBottom)
                            @php($maxChartValue = max(1, (float) $monthlyMax))
                            @php($stepX = $chartCount > 1 ? ($plotWidth / ($chartCount - 1)) : 0)
                            @php($tickEvery = $chartCount > 96 ? 12 : ($chartCount > 48 ? 6 : ($chartCount > 24 ? 3 : 1)))
                            @php($creditPoints = $chartRows->map(function ($row, $index) use ($padLeft, $padTop, $plotHeight, $stepX, $maxChartValue) {
                                $x = $padLeft + ($index * $stepX);
                                $y = $padTop + ($plotHeight - ((((float) ($row['credits'] ?? 0)) / $maxChartValue) * $plotHeight));
                                return number_format($x, 2, '.', '') . ',' . number_format($y, 2, '.', '');
                            })->implode(' '))
                            @php($debitPoints = $chartRows->map(function ($row, $index) use ($padLeft, $padTop, $plotHeight, $stepX, $maxChartValue) {
                                $x = $padLeft + ($index * $stepX);
                                $y = $padTop + ($plotHeight - ((((float) ($row['debits'] ?? 0)) / $maxChartValue) * $plotHeight));
                                return number_format($x, 2, '.', '') . ',' . number_format($y, 2, '.', '');
                            })->implode(' '))
                            <div class="mt-3 flex items-center gap-4 text-xs">
                                <span class="inline-flex items-center gap-2 text-green-700"><span class="w-4 h-0.5 bg-green-600"></span>Crédits</span>
                                <span class="inline-flex items-center gap-2 text-red-700"><span class="w-4 h-0.5 bg-red-600"></span>Débits</span>
                                <span class="text-gray-600">Max: <span class="tabular-nums whitespace-nowrap">{{ number_format($maxChartValue, 2, ',', ' ') }}</span></span>
                            </div>
                            <div class="mt-3 overflow-x-auto">
                                <svg class="min-w-[920px] w-full" viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" preserveAspectRatio="none" role="img" aria-label="Courbes mensuelles des crédits et débits">
                                    <rect x="0" y="0" width="{{ $chartWidth }}" height="{{ $chartHeight }}" fill="white"></rect>
                                    @for ($g = 0; $g <= 4; $g++)
                                        @php($yGrid = $padTop + (($plotHeight / 4) * $g))
                                        @php($labelValue = $maxChartValue * (1 - ($g / 4)))
                                        <line x1="{{ $padLeft }}" y1="{{ $yGrid }}" x2="{{ $chartWidth - $padRight }}" y2="{{ $yGrid }}" stroke="#E5E7EB" stroke-width="1" />
                                        <text x="{{ $padLeft - 8 }}" y="{{ $yGrid + 4 }}" text-anchor="end" font-size="11" fill="#4B5563">{{ number_format($labelValue, 0, ',', ' ') }}</text>
                                    @endfor
                                    <line x1="{{ $padLeft }}" y1="{{ $padTop }}" x2="{{ $padLeft }}" y2="{{ $chartHeight - $padBottom }}" stroke="#9CA3AF" stroke-width="1.2" />
                                    <line x1="{{ $padLeft }}" y1="{{ $chartHeight - $padBottom }}" x2="{{ $chartWidth - $padRight }}" y2="{{ $chartHeight - $padBottom }}" stroke="#9CA3AF" stroke-width="1.2" />
                                    <polyline fill="none" stroke="#16A34A" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" points="{{ $creditPoints }}" />
                                    <polyline fill="none" stroke="#DC2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" points="{{ $debitPoints }}" />
                                    @foreach ($chartRows as $index => $row)
                                        @php($xTick = $padLeft + ($index * $stepX))
                                        @php($creditY = $padTop + ($plotHeight - ((((float) ($row['credits'] ?? 0)) / $maxChartValue) * $plotHeight)))
                                        @php($debitY = $padTop + ($plotHeight - ((((float) ($row['debits'] ?? 0)) / $maxChartValue) * $plotHeight)))
                                        <circle cx="{{ $xTick }}" cy="{{ $creditY }}" r="1.8" fill="#16A34A">
                                            <title>{{ $row['month'] }} · Crédit: {{ number_format((float)($row['credits'] ?? 0), 2, ',', ' ') }} € · Débit: {{ number_format((float)($row['debits'] ?? 0), 2, ',', ' ') }} €</title>
                                        </circle>
                                        <circle cx="{{ $xTick }}" cy="{{ $debitY }}" r="1.8" fill="#DC2626">
                                            <title>{{ $row['month'] }} · Débit: {{ number_format((float)($row['debits'] ?? 0), 2, ',', ' ') }} € · Crédit: {{ number_format((float)($row['credits'] ?? 0), 2, ',', ' ') }} €</title>
                                        </circle>
                                        @if ($index % $tickEvery === 0 || $index === ($chartCount - 1))
                                            <line x1="{{ $xTick }}" y1="{{ $padTop }}" x2="{{ $xTick }}" y2="{{ $chartHeight - $padBottom }}" stroke="#F3F4F6" stroke-width="1" />
                                            <text x="{{ $xTick }}" y="{{ $chartHeight - 16 }}" text-anchor="middle" font-size="11" fill="#4B5563">{{ $row['month'] }}</text>
                                        @endif
                                    @endforeach
                                </svg>
                            </div>

                            <div class="mt-4">
                                <div class="font-medium text-sm">Résumé annuel (dates et montants précis)</div>
                                @if (($yearly_totals ?? collect())->count() === 0)
                                    <div class="mt-2 text-sm text-gray-600">Aucune donnée annuelle disponible.</div>
                                @else
                                    <div class="mt-2 overflow-x-auto">
                                        <table class="min-w-full text-sm">
                                            <thead>
                                                <tr class="text-left text-gray-600">
                                                    <th class="py-2 pr-4">Année</th>
                                                    <th class="py-2 pr-4 text-right">Crédits</th>
                                                    <th class="py-2 pr-4 text-right">Débits</th>
                                                    <th class="py-2 pr-4 text-right">Net</th>
                                                    <th class="py-2 pr-4 text-right">Mouvements</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y">
                                                @foreach (($yearly_totals ?? collect()) as $yearRow)
                                                    <tr>
                                                        <td class="py-2 pr-4 whitespace-nowrap">{{ $yearRow['year'] ?? '—' }}</td>
                                                        <td class="py-2 pr-4 text-right tabular-nums whitespace-nowrap text-green-700">{{ number_format((float)($yearRow['credits'] ?? 0), 2, ',', ' ') }}</td>
                                                        <td class="py-2 pr-4 text-right tabular-nums whitespace-nowrap text-red-700">{{ number_format((float)($yearRow['debits'] ?? 0), 2, ',', ' ') }}</td>
                                                        <td class="py-2 pr-4 text-right tabular-nums whitespace-nowrap {{ ((float)($yearRow['net'] ?? 0) < 0) ? 'text-red-700' : 'text-green-700' }}">{{ number_format((float)($yearRow['net'] ?? 0), 2, ',', ' ') }}</td>
                                                        <td class="py-2 pr-4 text-right tabular-nums whitespace-nowrap">{{ (int)($yearRow['count'] ?? 0) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="mt-4 border rounded-md p-4">
                        <div class="font-medium text-sm">Pics d’activité (montants élevés)</div>
                        <div class="text-xs text-gray-500 mt-1">Seuil actuel: {{ number_format((float)($behavioral['spike_threshold'] ?? 0), 2, ',', ' ') }}</div>
                        @if ($topSpikes->count() === 0)
                            <div class="mt-2 text-sm text-gray-600">Aucun pic significatif détecté.</div>
                        @else
                            <div class="mt-3 overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-600">
                                            <th class="py-2 pr-4">Date</th>
                                            <th class="py-2 pr-4">Libellé</th>
                                            <th class="py-2 pr-4">Type</th>
                                            <th class="py-2 pr-4 text-right">Montant</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        @foreach ($topSpikes as $spike)
                                            <tr>
                                                <td class="py-2 pr-4 whitespace-nowrap">{{ optional($spike->date)->format('Y-m-d') }}</td>
                                                <td class="py-2 pr-4 max-w-md truncate" title="{{ $spike->label }}">{{ $spike->label }}</td>
                                                <td class="py-2 pr-4">{{ ($spike->type ?? '') === 'debit' ? 'Débit' : 'Crédit' }}</td>
                                                <td class="py-2 pr-4 text-right font-medium whitespace-nowrap {{ ($spike->type ?? '') === 'debit' ? 'text-red-700' : 'text-green-700' }}">{{ number_format(abs((float)$spike->amount), 2, ',', ' ') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <hr class="my-6" />

                    <h3 class="font-semibold">Transactions</h3>

                    <div class="mt-3 border rounded-md p-4" x-data="{ showMotif: false }">
                        <form method="GET" action="{{ route('cases.show', $case) }}" class="grid grid-cols-1 md:grid-cols-10 gap-3 text-sm">
                            <div>
                                <x-input-label for="bank_name" :value="__('Banque')" />
                                <select id="bank_name" name="bank_name" class="mt-1 block w-full border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-md shadow-sm">
                                    <option value="" @selected(($tx_filters['bank_name'] ?? '') === '')>Toutes</option>
                                    @foreach (($account_filter_options['banks'] ?? collect()) as $bankName)
                                        <option value="{{ $bankName }}" @selected(($tx_filters['bank_name'] ?? '') === $bankName)>{{ $bankName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="account_profile" :value="__('Profil compte')" />
                                <select id="account_profile" name="account_profile" class="mt-1 block w-full border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-md shadow-sm">
                                    <option value="" @selected(($tx_filters['account_profile'] ?? '') === '')>Tous</option>
                                    <option value="personal" @selected(($tx_filters['account_profile'] ?? '') === 'personal')>Personnel</option>
                                    <option value="joint" @selected(($tx_filters['account_profile'] ?? '') === 'joint')>Commun</option>
                                </select>
                            </div>

                            <div>
                                <x-input-label for="bank_account_id" :value="__('Compte')" />
                                <select id="bank_account_id" name="bank_account_id" class="mt-1 block w-full border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-md shadow-sm">
                                    <option value="" @selected(($tx_filters['bank_account_id'] ?? '') === '')>Tous</option>
                                    @foreach (($account_filter_options['accounts'] ?? collect()) as $option)
                                        <option value="{{ $option['id'] }}" @selected((string)($tx_filters['bank_account_id'] ?? '') === (string)$option['id'])>{{ $option['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="date_from" :value="__('Du')" />
                                <x-text-input id="date_from" name="date_from" type="date" class="mt-1 block w-full" :value="$tx_filters['date_from'] ?? ''" />
                            </div>
                            <div>
                                <x-input-label for="date_to" :value="__('Au')" />
                                <x-text-input id="date_to" name="date_to" type="date" class="mt-1 block w-full" :value="$tx_filters['date_to'] ?? ''" />
                            </div>
                            <div>
                                <x-input-label for="min_amount" :value="__('Montant min (valeur absolue)')" />
                                <x-text-input id="min_amount" name="min_amount" type="number" step="0.01" class="mt-1 block w-full" :value="$tx_filters['min_amount'] ?? ''" />
                            </div>
                            <div>
                                <x-input-label for="max_amount" :value="__('Montant max (valeur absolue)')" />
                                <x-text-input id="max_amount" name="max_amount" type="number" step="0.01" class="mt-1 block w-full" :value="$tx_filters['max_amount'] ?? ''" />
                            </div>
                            <div>
                                <x-input-label for="type" :value="__('Sens')" />
                                <select id="type" name="type" class="mt-1 block w-full border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-md shadow-sm">
                                    <option value="" @selected(($tx_filters['type'] ?? '') === '')>Tous</option>
                                    <option value="debit" @selected(($tx_filters['type'] ?? '') === 'debit')>Débit</option>
                                    <option value="credit" @selected(($tx_filters['type'] ?? '') === 'credit')>Crédit</option>
                                </select>
                            </div>
                            <div>
                                <x-input-label for="kind" :value="__('Catégorie')" />
                                <select id="kind" name="kind" class="mt-1 block w-full border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-md shadow-sm">
                                    <option value="" @selected(($tx_filters['kind'] ?? '') === '')>Toutes</option>
                                    <option value="transfer" @selected(($tx_filters['kind'] ?? '') === 'transfer')>Virement</option>
                                    <option value="cheque" @selected(($tx_filters['kind'] ?? '') === 'cheque')>Chèque</option>
                                    <option value="cash_withdrawal" @selected(($tx_filters['kind'] ?? '') === 'cash_withdrawal')>Retrait espèces</option>
                                    <option value="other" @selected(($tx_filters['kind'] ?? '') === 'other')>Autre</option>
                                </select>
                            </div>
                            <div>
                                <x-input-label for="score_min" :value="__('Score min')" />
                                <x-text-input id="score_min" name="score_min" type="number" min="0" max="100" step="1" class="mt-1 block w-full" :value="$tx_filters['score_min'] ?? ''" />
                            </div>
                            <div>
                                <x-input-label for="q" :value="__('Recherche texte')" />
                                <x-text-input id="q" name="q" type="text" class="mt-1 block w-full" :value="$tx_filters['q'] ?? ''" placeholder="Libellé, motif, origine..." />
                            </div>

                            @php($activeFilters = collect([
                                'Du' => $tx_filters['date_from'] ?? null,
                                'Au' => $tx_filters['date_to'] ?? null,
                                'Banque' => $tx_filters['bank_name'] ?? null,
                                'Profil compte' => ($tx_filters['account_profile'] ?? '') === 'joint' ? 'Commun' : ((($tx_filters['account_profile'] ?? '') === 'personal') ? 'Personnel' : null),
                                'Compte' => $tx_filters['bank_account_id'] ?? null,
                                'Montant min' => $tx_filters['min_amount'] ?? null,
                                'Montant max' => $tx_filters['max_amount'] ?? null,
                                'Sens' => $tx_filters['type'] ?? null,
                                'Catégorie' => $tx_filters['kind'] ?? null,
                                'Score min' => $tx_filters['score_min'] ?? null,
                                'Texte' => $tx_filters['q'] ?? null,
                            ])->filter(fn ($value) => !is_null($value) && $value !== ''))

                            @if ($activeFilters->isNotEmpty())
                                <div class="md:col-span-6 text-xs text-gray-700 bg-green-50 border border-green-100 rounded-md px-3 py-2">
                                    Filtres actifs:
                                    @foreach ($activeFilters as $label => $value)
                                        <span class="inline-flex items-center px-2 py-0.5 ml-2 mt-1 rounded bg-white border border-green-200 whitespace-nowrap">{{ $label }}: {{ $value }}</span>
                                    @endforeach
                                </div>
                            @endif

                            <div class="md:col-span-6 flex flex-wrap items-center gap-3">
                                <x-primary-button>
                                    Filtrer
                                </x-primary-button>
                                <a class="text-sm text-gray-600 hover:underline" href="{{ route('cases.show', $case) }}">Réinitialiser</a>
                                <button type="button" @click="showMotif = !showMotif" class="inline-flex items-center px-3 py-2 text-xs border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    <span x-text="showMotif ? 'Masquer la colonne Motif' : 'Afficher la colonne Motif'"></span>
                                </button>
                                <div class="text-sm text-gray-600">
                                    <span class="text-gray-500">("valeur absolue" = on ignore le signe: 50€ filtre -50€ et +50€)</span>
                                    Total débit: <span class="font-medium whitespace-nowrap">{{ number_format((float)($tx_totals['debit'] ?? 0), 2, ',', ' ') }}</span>
                                    · Total crédit: <span class="font-medium whitespace-nowrap">{{ number_format((float)($tx_totals['credit'] ?? 0), 2, ',', ' ') }}</span>
                                    · Net: <span class="font-medium whitespace-nowrap">{{ number_format((float)($tx_totals['net'] ?? 0), 2, ',', ' ') }}</span>
                                    · Lignes: <span class="font-medium">{{ (int)($tx_totals['count'] ?? 0) }}</span>
                                </div>
                            </div>
                        </form>

                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-600">
                                        <th class="py-2 pr-4">Date</th>
                                        <th class="py-2 pr-4">Sens</th>
                                        <th class="py-2 pr-4">Catégorie</th>
                                        <th class="py-2 pr-4">Origine</th>
                                        <th class="py-2 pr-4">Destinataire</th>
                                        <th class="py-2 pr-4" x-show="showMotif" x-cloak>Motif</th>
                                        <th class="py-2 pr-4">N° chèque</th>
                                        <th class="py-2 pr-4 text-right">Montant</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @forelse (($transactions ?? []) as $tx)
                                        <tr>
                                            <td class="py-3 pr-4 whitespace-nowrap">{{ optional($tx->date)->format('Y-m-d') }}</td>
                                            <td class="py-3 pr-4">{{ ($tx->type ?? '') === 'debit' ? 'débit' : (($tx->type ?? '') === 'credit' ? 'crédit' : '—') }}</td>
                                            <td class="py-3 pr-4">{{ $tx->kind ?? '—' }}</td>
                                            <td class="py-3 pr-4 whitespace-normal break-words" title="{{ $tx->origin ?? '' }}">{{ $tx->origin ?? '—' }}</td>
                                            <td class="py-3 pr-4 whitespace-normal break-words" title="{{ $tx->destination ?? '' }}">{{ $tx->destination ?? '—' }}</td>
                                            <td class="py-3 pr-4 whitespace-normal break-words max-w-[34rem]" x-show="showMotif" x-cloak title="{{ $tx->motif ?? $tx->label }}">{{ $tx->motif ?? $tx->label }}</td>
                                            <td class="py-3 pr-4">{{ $tx->cheque_number ?? '—' }}</td>
                                            <td class="py-3 pr-4 text-right tabular-nums whitespace-nowrap">{{ number_format(abs((float)$tx->amount), 2, ',', ' ') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td class="py-3 pr-4 text-gray-600" colspan="8">Aucune transaction (ou aucun filtre ne correspond).</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if (isset($transactions) && method_exists($transactions, 'links'))
                            <div class="mt-4">
                                {{ $transactions->links() }}
                            </div>
                        @endif
                    </div>

                    <hr class="my-6" />

                    <h3 class="font-semibold">Assistant IA (demande libre)</h3>

                    <div class="mt-3 border rounded-md p-4">
                        @if ($aiError)
                            <div class="mb-3 text-sm text-red-700">
                                {{ $aiError }}
                            </div>
                        @endif

                        <div class="mb-3 text-xs text-gray-600">
                            Cette section garde la dernière demande IA envoyée pour ce dossier, afin que tu voies toujours ce qui a été demandé et le résultat obtenu.
                        </div>

                        <form method="POST" action="{{ route('cases.ai', $case) }}" class="space-y-3">
                            @csrf
                            <div>
                                <x-input-label for="prompt" :value="__('Ta demande (optionnel)')" />
                                <textarea id="prompt" name="prompt" rows="3" class="mt-1 block w-full border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-md shadow-sm" placeholder="Ex: Montre-moi les virements suspects sur 3 mois avant le décès, > 2 000 €.">{{ old('prompt', (string) ($lastAi['prompt'] ?? '')) }}</textarea>
                            </div>
                            <x-primary-button>
                                Analyser avec IA
                            </x-primary-button>
                        </form>

                        @if ($ai)
                            <div class="mt-4 space-y-3 text-sm">
                                @if (($ai['summary'] ?? '') !== '')
                                    <div>
                                        <div class="font-medium">Résumé</div>
                                        <div class="text-gray-700">{{ $ai['summary'] }}</div>
                                    </div>
                                @endif

                                <div>
                                    <div class="font-medium">Points d’attention</div>
                                    @if (count(($ai['suspicious'] ?? [])) === 0)
                                        <div class="text-gray-600">—</div>
                                    @else
                                        <ul class="mt-1 list-disc ps-5 space-y-1">
                                            @foreach (($ai['suspicious'] ?? []) as $s)
                                                <li>{{ $s }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>

                                <div>
                                    <div class="font-medium">Filtres suggérés</div>
                                    @if (count(($ai['filters'] ?? [])) === 0)
                                        <div class="text-gray-600">—</div>
                                    @else
                                        <ul class="mt-1 space-y-1">
                                            @foreach (($ai['filters'] ?? []) as $filterKey => $filterValue)
                                                <li class="text-gray-700">
                                                    <span class="font-medium">{{ $filterKey }}:</span>
                                                    {{ is_scalar($filterValue) || is_null($filterValue) ? ($filterValue === '' || is_null($filterValue) ? '—' : $filterValue) : json_encode($filterValue, JSON_UNESCAPED_UNICODE) }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                    <h3 class="font-semibold">Comptes</h3>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <form method="POST" action="{{ route('bank-accounts.store', $case) }}" class="space-y-3">
                                @csrf

                                <div>
                                    <x-input-label for="bank_name" :value="__('Banque')" />
                                    <x-text-input id="bank_name" name="bank_name" type="text" class="mt-1 block w-full" required />
                                </div>

                                <div>
                                    <x-input-label for="iban_masked" :value="__('IBAN masqué (optionnel)')" />
                                    <x-text-input id="iban_masked" name="iban_masked" type="text" class="mt-1 block w-full" />
                                </div>

                                <div>
                                    <x-input-label for="account_holder" :value="__('Titulaire (optionnel)')" />
                                    <x-text-input id="account_holder" name="account_holder" type="text" class="mt-1 block w-full" />
                                </div>

                                <x-primary-button>
                                    {{ __('Ajouter compte') }}
                                </x-primary-button>
                            </form>
                        </div>

                        <div>
                            @if ($case->bankAccounts->count() === 0)
                                <p class="text-sm text-gray-600">Ajoute un compte pour pouvoir importer un relevé.</p>
                            @else
                                <div class="space-y-4">
                                    @foreach ($case->bankAccounts as $account)
                                        <div class="border rounded-md p-4">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <div class="font-medium">{{ $account->bank_name }}</div>
                                                    <div class="text-sm text-gray-600">{{ $account->iban_masked ?? '—' }}</div>
                                                </div>
                                            </div>

                                            <div class="mt-3">
                                                <form method="POST" action="{{ route('statements.store', $account) }}" enctype="multipart/form-data" class="space-y-2">
                                                    @csrf
                                                    <input type="file" name="statement" required class="block w-full text-sm" />
                                                    <p class="text-xs text-gray-500">MVP: CSV conseillé (colonnes: date,label,amount,balance_after).</p>
                                                    <x-primary-button>
                                                        {{ __('Uploader relevé') }}
                                                    </x-primary-button>
                                                </form>
                                            </div>

                                            <div class="mt-3 text-sm text-gray-600">
                                                Relevés: {{ $account->statements->count() }}
                                                @if ($account->statements->count() > 0)
                                                    <div class="mt-2 space-y-1">
                                                        @foreach ($account->statements->sortByDesc('created_at')->take(3) as $st)
                                                            <div class="flex items-center justify-between gap-3 text-xs text-gray-500">
                                                                <div class="min-w-0">
                                                                    <span class="truncate">{{ $st->original_filename ?? 'relevé' }}</span>
                                                                    — {{ $st->import_status ?? '—' }}
                                                                    @if (!is_null($st->transactions_imported ?? null))
                                                                        ({{ $st->transactions_imported }})
                                                                    @endif
                                                                    @if ($st->ocr_used)
                                                                        · OCR
                                                                    @endif
                                                                    @if ($st->import_error)
                                                                        · {{ Str::limit($st->import_error, 120) }}
                                                                    @endif
                                                                </div>

                                                                <form method="POST" action="{{ route('statements.destroy', $st) }}" onsubmit="return confirm('Supprimer ce document ? Cette action retire aussi les transactions importées liées à ce relevé si tu les as nettoyées manuellement.');">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <x-danger-button class="px-3 py-1 text-[10px]">
                                                                        Supprimer
                                                                    </x-danger-button>
                                                                </form>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach

                                    <div class="border rounded-md p-4 bg-gray-50">
                                        <div class="font-medium text-sm">Diagnostic import (contrôle qualité)</div>
                                        <div class="text-xs text-gray-500 mt-1">Seuil montants élevés OCR: {{ number_format((float)($import_high_value_threshold ?? 20000), 0, ',', ' ') }} €</div>

                                        @if (($statement_diagnostics ?? collect())->count() === 0)
                                            <div class="mt-2 text-sm text-gray-600">Aucun relevé disponible pour diagnostic.</div>
                                        @else
                                            <div class="mt-3 overflow-x-auto">
                                                <table class="min-w-full text-xs">
                                                    <thead>
                                                        <tr class="text-left text-gray-600">
                                                            <th class="py-2 pr-3">Relevé</th>
                                                            <th class="py-2 pr-3">Statut</th>
                                                            <th class="py-2 pr-3 text-right">Tx importées</th>
                                                            <th class="py-2 pr-3 text-right">Montants élevés OCR</th>
                                                            <th class="py-2 pr-3 text-right">Montants élevés importés</th>
                                                            <th class="py-2 pr-3">Alerte</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y">
                                                        @foreach (($statement_diagnostics ?? collect()) as $diag)
                                                            @php($st = $diag['statement'])
                                                            <tr>
                                                                <td class="py-2 pr-3 align-top">
                                                                    <div class="font-medium text-gray-700">{{ $diag['bank_name'] ?? 'Compte' }}</div>
                                                                    <div class="text-gray-600 truncate max-w-[280px]" title="{{ $st->original_filename ?? '' }}">{{ $st->original_filename ?? 'relevé' }}</div>
                                                                </td>
                                                                <td class="py-2 pr-3 align-top">
                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded border text-[10px] {{ ($st->import_status ?? '') === 'completed' ? 'bg-green-50 text-green-700 border-green-200' : ((($st->import_status ?? '') === 'failed') ? 'bg-red-50 text-red-700 border-red-200' : 'bg-gray-50 text-gray-700 border-gray-200') }}">
                                                                        {{ $st->import_status ?? '—' }}
                                                                    </span>
                                                                    @if (!empty($st->ocr_used))
                                                                        <span class="ml-1 text-[10px] text-gray-500">OCR</span>
                                                                    @endif
                                                                </td>
                                                                <td class="py-2 pr-3 align-top text-right tabular-nums">{{ (int)($st->transactions_imported ?? 0) }}</td>
                                                                <td class="py-2 pr-3 align-top text-right tabular-nums">
                                                                    {{ (int)($diag['ocr_high_values_count'] ?? 0) }}
                                                                    @if (($diag['ocr_high_values_count'] ?? 0) > 0)
                                                                        <div class="text-[10px] text-gray-500 whitespace-nowrap">max {{ number_format((float)($diag['ocr_high_values'][0] ?? 0), 2, ',', ' ') }}</div>
                                                                    @endif
                                                                </td>
                                                                <td class="py-2 pr-3 align-top text-right tabular-nums">{{ is_null($diag['imported_high_values_count']) ? '—' : (int)$diag['imported_high_values_count'] }}</td>
                                                                <td class="py-2 pr-3 align-top">
                                                                    @if (!empty($diag['suspect_missing']))
                                                                        <span class="inline-flex items-center px-2 py-0.5 rounded border text-[10px] bg-red-50 text-red-700 border-red-200">Montants élevés manquants</span>
                                                                    @elseif (($diag['ocr_high_values_count'] ?? 0) > 0)
                                                                        <span class="inline-flex items-center px-2 py-0.5 rounded border text-[10px] bg-green-50 text-green-700 border-green-200">Contrôle OK</span>
                                                                    @else
                                                                        <span class="text-[10px] text-gray-500">—</span>
                                                                    @endif
                                                                    @if (!empty($st->import_error))
                                                                        <div class="mt-1 text-[10px] text-red-700 max-w-[260px]">{{ Str::limit((string) $st->import_error, 160) }}</div>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
