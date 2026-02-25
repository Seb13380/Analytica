<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; margin: 18px; }
        h1 { font-size: 20px; margin: 0; }
        h2 { font-size: 14px; margin: 18px 0 8px 0; }
        h3 { font-size: 12px; margin: 12px 0 6px 0; }
        p { margin: 4px 0; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 6px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; font-weight: 700; }
        .right { text-align: right; }
        .header { border-bottom: 2px solid #111827; padding-bottom: 10px; margin-bottom: 10px; }
        .kpi-grid { width: 100%; margin-top: 8px; }
        .kpi-grid td { border: 1px solid #e5e7eb; padding: 8px; width: 33.33%; }
        .kpi-label { font-size: 10px; color: #6b7280; }
        .kpi-value { font-size: 14px; font-weight: 700; margin-top: 2px; }
        .notice { border: 1px solid #d1d5db; background: #f9fafb; padding: 8px; margin-top: 8px; }
        .pill { display: inline-block; border: 1px solid #d1d5db; border-radius: 999px; padding: 2px 6px; font-size: 10px; color: #374151; }
        .spacer { height: 8px; }
        .small { font-size: 10px; }
    </style>
</head>
<body>
    @php($ruleLabels = [
        'R1_high_amount' => 'Montant exceptionnel (vs moyenne du mois)',
        'R2_recurrent_beneficiary' => 'Bénéficiaire récurrent inhabituel',
        'R3_pre_death' => 'Opération en période sensible (avant décès)',
        'R4_structuring' => 'Fractionnement (montants proches, période courte)',
        'R5_large_cash_withdrawal' => 'Retrait espèces anormalement élevé',
        'R6_zscore_break' => 'Rupture statistique (z-score > 2)',
    ])

    <div class="header">
        <h1>Analytica — Rapport d’analyse</h1>
        <p class="muted">Dossier #{{ $case->id }} — {{ $case->title }}</p>
        <p>Généré le : {{ now()->format('d/m/Y H:i') }} · Score global (0–100) : <strong>{{ $case->global_score ?? '—' }}</strong></p>
    </div>

    <div class="notice">
        Rapport neutre: ce document met en évidence des signaux statistiques et des incohérences potentielles.
        Il ne constitue pas une accusation et ne conclut pas à une intention.
    </div>

    <h2>Résumé exécutif</h2>
    <table class="kpi-grid">
        <tr>
            <td>
                <div class="kpi-label">Transactions importées</div>
                <div class="kpi-value">{{ $totalTransactions }}</div>
            </td>
            <td>
                <div class="kpi-label">Transactions signalées</div>
                <div class="kpi-value">{{ $totalFlagged }}</div>
            </td>
            <td>
                <div class="kpi-label">Montant signalé (abs.)</div>
                <div class="kpi-value">{{ number_format($totalFlaggedAmount, 2, ',', ' ') }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="kpi-label">Total débit (abs.)</div>
                <div class="kpi-value">{{ number_format($totalDebit, 2, ',', ' ') }}</div>
            </td>
            <td>
                <div class="kpi-label">Total crédit</div>
                <div class="kpi-value">{{ number_format($totalCredit, 2, ',', ' ') }}</div>
            </td>
            <td>
                <div class="kpi-label">Net (crédit - débit)</div>
                <div class="kpi-value">{{ number_format($netAmount, 2, ',', ' ') }}</div>
            </td>
        </tr>
    </table>

    <h2>Qualité des imports</h2>
    <p>
        <span class="pill">Relevés: {{ $statementStats['total'] ?? 0 }}</span>
        <span class="pill">Complétés: {{ $statementStats['completed'] ?? 0 }}</span>
        <span class="pill">En échec: {{ $statementStats['failed'] ?? 0 }}</span>
        <span class="pill">OCR utilisé: {{ $statementStats['ocr_used'] ?? 0 }}</span>
    </p>
    <p class="muted small">Transactions extraites côté import: {{ $statementStats['transactions_imported'] ?? 0 }}. En cas de scan flou, privilégier l’export Excel pour affiner le contrôle.</p>

    <h2>Méthodologie</h2>
    <p>
        Analytica applique des règles pondérées et des vérifications de cohérence sur les transactions importées.
        Chaque transaction reçoit un score de 0 à 100 : normal (&lt;30), atypique (30–59), fortement atypique (&ge;60).
    </p>

    <h2>Tableau des anomalies (extrait)</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Libellé</th>
                <th class="right">Montant</th>
                <th>Type</th>
                <th class="right">Score</th>
                <th>Règles</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($flaggedTransactions as $tx)
                <tr>
                    <td>{{ optional($tx->date)->format('Y-m-d') }}</td>
                    <td>{{ $tx->label }}</td>
                    <td class="right">{{ number_format((float) $tx->amount, 2, ',', ' ') }}</td>
                    <td>{{ $tx->type === 'credit' ? 'Crédit' : ($tx->type === 'debit' ? 'Débit' : ($tx->type ?? '—')) }}</td>
                    <td class="right">{{ $tx->anomaly_score ?? '—' }}</td>
                    <td>
                        @if (is_array($tx->rule_flags))
                            @php($activeRules = array_keys(array_filter($tx->rule_flags)))
                            @if (count($activeRules) === 0)
                                —
                            @else
                                {{ collect($activeRules)->map(fn ($rule) => $ruleLabels[$rule] ?? $rule)->implode(', ') }}
                            @endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">Aucune transaction signalée.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Analyse bénéficiaires (Top)</h2>
    <table>
        <thead>
            <tr>
                <th>Bénéficiaire / Libellé normalisé</th>
                <th class="right">Montant cumulé (abs.)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($topBeneficiaries as $beneficiary => $amount)
                <tr>
                    <td>{{ $beneficiary !== '' ? $beneficiary : '—' }}</td>
                    <td class="right">{{ number_format((float) $amount, 2, ',', ' ') }}</td>
                </tr>
            @empty
                <tr><td colspan="2" class="muted">Aucun bénéficiaire identifié dans les transactions signalées.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Transactions importées (échantillon)</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Libellé</th>
                <th>Type</th>
                <th class="right">Montant</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($allTransactionsSample as $tx)
                <tr>
                    <td>{{ optional($tx->date)->format('Y-m-d') }}</td>
                    <td>{{ $tx->label }}</td>
                    <td>{{ $tx->type }}</td>
                    <td class="right">{{ number_format((float) $tx->amount, 2, ',', ' ') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">Aucune transaction disponible.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Annexes</h2>
    <p class="muted">Données sources: relevés importés (chiffrés au stockage), empreintes SHA256, horodatages d’import.</p>

</body>
</html>
