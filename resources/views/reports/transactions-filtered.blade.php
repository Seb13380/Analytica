<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 400;
            src: url('{{ $poppinsRegularPath }}') format('truetype');
        }
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 600;
            src: url('{{ $poppinsSemiBoldPath }}') format('truetype');
        }

        * { box-sizing: border-box; }
        body {
            font-family: 'Poppins', DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111827;
            margin: 16px 20px;
        }

        /* ── Header ── */
        .header {
            border-bottom: 2px solid #111827;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .header h1 { font-size: 16px; font-weight: 600; margin: 0 0 2px 0; }
        .header p  { margin: 1px 0; color: #6b7280; font-size: 9px; }

        /* ── Filters badge row ── */
        .filters-row { margin: 6px 0 10px 0; font-size: 9px; color: #374151; }
        .badge {
            display: inline-block;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 1px 5px;
            margin-right: 4px;
            background: #f9fafb;
        }

        /* ── KPI summary bar ── */
        .kpi-bar {
            display: table;
            width: 100%;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            background: #f9fafb;
            margin-bottom: 12px;
        }
        .kpi-bar td {
            display: table-cell;
            width: 25%;
            padding: 6px 10px;
            border-right: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        .kpi-bar td:last-child { border-right: none; }
        .kpi-label { font-size: 9px; color: #6b7280; margin-bottom: 1px; }
        .kpi-value { font-size: 13px; font-weight: 700; }
        .kpi-debit  { color: #b91c1c; }
        .kpi-credit { color: #15803d; }
        .kpi-net-pos { color: #15803d; }
        .kpi-net-neg { color: #b91c1c; }

        /* ── Transaction table ── */
        table.tx-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        table.tx-table th {
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            padding: 5px 6px;
            text-align: left;
            font-weight: 600;
            font-size: 9px;
            white-space: nowrap;
        }
        table.tx-table td {
            border: 1px solid #e5e7eb;
            padding: 4px 6px;
            vertical-align: top;
            font-size: 9px;
            word-break: break-word;
        }
        table.tx-table tr:nth-child(even) td { background: #f9fafb; }
        table.tx-table tr.debit  td.amount { color: #b91c1c; }
        table.tx-table tr.credit td.amount { color: #15803d; }
        .amount { text-align: right; white-space: nowrap; font-weight: 600; }
        .muted   { color: #9ca3af; }

        /* ── Footer totals row ── */
        tfoot td {
            border-top: 2px solid #374151;
            font-weight: 700;
            font-size: 9.5px;
            padding: 5px 6px;
            background: #f3f4f6;
        }

        /* ── Page footer ── */
        .page-footer { margin-top: 14px; font-size: 8px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 5px; }

        /* DomPDF page break helper */
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Analytica — Transactions filtrées</h1>
        <p>Dossier #{{ $case->id }} &mdash; {{ e($case->title) }}</p>
        @if (!empty($case->deceased_name))
            <p>Défunt·e : {{ e($case->deceased_name) }}{{ !empty($case->death_date) ? ' · Décès : ' . \Carbon\Carbon::parse($case->death_date)->format('d/m/Y') : '' }}</p>
        @endif
        <p>Document généré le {{ $generatedAt }}</p>
    </div>

    {{-- Active filters --}}
    @if ($activeFilters->isNotEmpty())
        <div class="filters-row">
            <strong>Filtres appliqués :</strong>
            @foreach ($activeFilters as $label => $value)
                <span class="badge">{{ $label }} : {{ $value }}</span>
            @endforeach
        </div>
    @endif

    {{-- KPI bar --}}
    <table class="kpi-bar" style="border-collapse:separate;">
        <tr>
            <td>
                <div class="kpi-label">Lignes</div>
                <div class="kpi-value">{{ number_format($transactions->count()) }}</div>
            </td>
            <td>
                <div class="kpi-label">Total débits</div>
                <div class="kpi-value kpi-debit">{{ number_format($totalDebit, 2, ',', ' ') }} €</div>
            </td>
            <td>
                <div class="kpi-label">Total crédits</div>
                <div class="kpi-value kpi-credit">{{ number_format($totalCredit, 2, ',', ' ') }} €</div>
            </td>
            <td>
                <div class="kpi-label">Net (crédit &minus; débit)</div>
                <div class="kpi-value {{ $net >= 0 ? 'kpi-net-pos' : 'kpi-net-neg' }}">
                    {{ ($net >= 0 ? '+' : '') . number_format($net, 2, ',', ' ') }} €
                </div>
            </td>
        </tr>
    </table>

    {{-- Transaction table --}}
    <table class="tx-table">
        <thead>
            <tr>
                <th style="width:7%">Date</th>
                <th style="width:6%">Sens</th>
                <th style="width:7%">Catégorie</th>
                <th style="width:15%">Origine</th>
                <th style="width:15%">Destinataire</th>
                <th style="width:37%">Libellé / Motif</th>
                <th style="width:6%">N° chèque</th>
                <th style="width:7%">Montant</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transactions as $tx)
                @php
                    $typeClass = ($tx->type ?? '') === 'debit' ? 'debit' : (($tx->type ?? '') === 'credit' ? 'credit' : '');
                    $typeLabel = ($tx->type ?? '') === 'debit' ? 'Débit' : (($tx->type ?? '') === 'credit' ? 'Crédit' : '—');
                    $sign      = ($tx->type ?? '') === 'debit' ? '−' : (($tx->type ?? '') === 'credit' ? '+' : '');
                @endphp
                <tr class="{{ $typeClass }}">
                    <td>{{ optional($tx->date)->format('d/m/Y') }}</td>
                    <td>{{ $typeLabel }}</td>
                    <td>{{ $tx->kind ?? '—' }}</td>
                    <td>{{ ($tx->display_origin ?? '') !== '' ? $tx->display_origin : '—' }}</td>
                    <td>{{ ($tx->display_destination ?? '') !== '' ? $tx->display_destination : '—' }}</td>
                    <td>{{ $tx->display_label ?? '—' }}</td>
                    <td>{{ $tx->cheque_number ?? '—' }}</td>
                    <td class="amount">{{ $sign }}{{ number_format(abs((float) $tx->amount), 2, ',', ' ') }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted" style="text-align:center;padding:12px;">Aucune transaction.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7" style="text-align:right;">Total débits &nbsp; {{ number_format($totalDebit, 2, ',', ' ') }} € &nbsp;|&nbsp; Total crédits &nbsp; {{ number_format($totalCredit, 2, ',', ' ') }} € &nbsp;|&nbsp; Net &nbsp; {{ ($net >= 0 ? '+' : '') . number_format($net, 2, ',', ' ') }} €</td>
                <td class="amount">{{ number_format($transactions->count()) }} lignes</td>
            </tr>
        </tfoot>
    </table>

    <div class="page-footer">
        Analytica — usage strictement confidentiel · Généré le {{ $generatedAt }} · Dossier #{{ $case->id }}
    </div>
</body>
</html>
