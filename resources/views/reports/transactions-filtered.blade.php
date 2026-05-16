<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
    @font-face { font-family:'Poppins'; font-weight:400; src:url('{{ $poppinsRegularPath }}') format('truetype'); }
    @font-face { font-family:'Poppins'; font-weight:700; src:url('{{ $poppinsSemiBoldPath }}') format('truetype'); }

    * { box-sizing:border-box; }
    body { font-family:'Poppins',DejaVu Sans,sans-serif; font-size:9px; color:#111827; margin:14px 18px; }

    .header { border-bottom:2px solid #111827; padding-bottom:7px; margin-bottom:8px; }
    .header h1 { font-size:15px; font-weight:700; margin:0 0 2px 0; }
    .header p  { margin:1px 0; color:#6b7280; font-size:8.5px; }

    .filters { margin:5px 0 9px 0; font-size:8.5px; color:#374151; }
    .badge { display:inline-block; border:1px solid #d1d5db; border-radius:3px; padding:1px 4px; margin-right:3px; background:#f9fafb; }

    .kpi { width:100%; border-collapse:collapse; margin-bottom:10px; border:1px solid #e5e7eb; }
    .kpi td { width:25%; padding:5px 10px; border-right:1px solid #e5e7eb; }
    .kpi td:last-child { border-right:none; }
    .kl { font-size:8px; color:#6b7280; }
    .kv { font-size:12px; font-weight:700; }
    .red { color:#b91c1c; }
    .grn { color:#15803d; }

    table.tx { width:100%; border-collapse:collapse; }
    table.tx th { background:#f3f4f6; border:1px solid #e5e7eb; padding:4px 5px; text-align:left; font-weight:700; font-size:8.5px; white-space:nowrap; }
    table.tx th.r { text-align:right; }
    table.tx td { border:1px solid #e5e7eb; padding:3px 5px; vertical-align:top; font-size:8.5px; word-break:break-word; }
    table.tx tr:nth-child(even) td { background:#f9fafb; }
    td.dbt { color:#b91c1c; font-weight:700; text-align:right; white-space:nowrap; }
    td.cdt { color:#15803d; font-weight:700; text-align:right; white-space:nowrap; }
    td.emp { color:#d1d5db; text-align:right; }

    tfoot td { border-top:2px solid #374151; font-weight:700; font-size:9px; padding:4px 5px; background:#f3f4f6; }
    tfoot td.r { text-align:right; }

    .foot { margin-top:12px; font-size:7.5px; color:#9ca3af; border-top:1px solid #e5e7eb; padding-top:4px; }
</style>
</head>
<body>

<div class="header">
    <h1>Analytica &mdash; Transactions filtrees</h1>
    <p>Dossier #{{ $case->id }} &mdash; {{ e($case->title) }}</p>
    @if (!empty($case->deceased_name))
        <p>Defunt(e) : {{ e($case->deceased_name) }}{{ !empty($case->death_date) ? ' | Deces : ' . \Carbon\Carbon::parse($case->death_date)->format('d/m/Y') : '' }}</p>
    @endif
    <p>Document genere le {{ $generatedAt }}</p>
</div>

@if ($activeFilters->isNotEmpty())
<div class="filters">
    <strong>Filtres :</strong>
    @foreach ($activeFilters as $lbl => $val)
        <span class="badge">{{ $lbl }} : {{ $val }}</span>
    @endforeach
</div>
@endif

<table class="kpi">
    <tr>
        <td><div class="kl">Lignes</div><div class="kv">{{ number_format($transactions->count()) }}</div></td>
        <td><div class="kl">Total debits</div><div class="kv red">{{ number_format($totalDebit, 2, ',', ' ') }} &euro;</div></td>
        <td><div class="kl">Total credits</div><div class="kv grn">{{ number_format($totalCredit, 2, ',', ' ') }} &euro;</div></td>
        <td>
            <div class="kl">Net (credits &ndash; debits)</div>
            <div class="kv {{ $net >= 0 ? 'grn' : 'red' }}">
                @if ($net >= 0){{ number_format($net, 2, ',', ' ') }} &euro;
                @else({{ number_format(abs($net), 2, ',', ' ') }}) &euro;@endif
            </div>
        </td>
    </tr>
</table>

<table class="tx">
    <thead>
        <tr>
            <th style="width:7%">Date</th>
            <th style="width:7%">Categorie</th>
            <th style="width:13%">Origine</th>
            <th style="width:13%">Destinataire</th>
            <th style="width:42%">Libelle / Motif</th>
            <th style="width:5%">Cheque</th>
            <th style="width:6.5%" class="r">Debit</th>
            <th style="width:6.5%" class="r">Credit</th>
        </tr>
    </thead>
    <tbody>
    @forelse ($transactions as $tx)
        @php
            $isDebit  = ($tx->type ?? '') === 'debit';
            $isCredit = ($tx->type ?? '') === 'credit';
            $amt = number_format(abs((float) $tx->amount), 2, ',', ' ');
        @endphp
        <tr>
            <td>{{ optional($tx->date)->format('d/m/Y') }}</td>
            <td>{{ $tx->kind ?? '--' }}</td>
            <td>{{ $tx->display_origin ?? '--' }}</td>
            <td>{{ $tx->display_destination ?? '--' }}</td>
            <td>{{ $tx->display_label ?? '--' }}</td>
            <td>{{ $tx->cheque_number ?? '--' }}</td>
            <td class="{{ $isDebit ? 'dbt' : 'emp' }}">{{ $isDebit ? $amt : '' }}</td>
            <td class="{{ $isCredit ? 'cdt' : 'emp' }}">{{ $isCredit ? $amt : '' }}</td>
        </tr>
    @empty
        <tr><td colspan="8" style="text-align:center;padding:10px;color:#9ca3af;">Aucune transaction.</td></tr>
    @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6" class="r">
                Total debits : {{ number_format($totalDebit, 2, ',', ' ') }} &euro;
                &nbsp;|&nbsp; Total credits : {{ number_format($totalCredit, 2, ',', ' ') }} &euro;
                &nbsp;|&nbsp; Net :
                @if ($net >= 0){{ number_format($net, 2, ',', ' ') }} &euro;
                @else({{ number_format(abs($net), 2, ',', ' ') }}) &euro;@endif
            </td>
            <td class="r dbt">{{ number_format($totalDebit, 2, ',', ' ') }}</td>
            <td class="r cdt">{{ number_format($totalCredit, 2, ',', ' ') }}</td>
        </tr>
    </tfoot>
</table>

<div class="foot">Analytica &mdash; usage strictement confidentiel &mdash; {{ $generatedAt }} &mdash; Dossier #{{ $case->id }}</div>
</body>
</html>
