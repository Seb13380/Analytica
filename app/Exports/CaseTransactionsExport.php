<?php

namespace App\Exports;

use App\Models\CaseFile;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CaseTransactionsExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        private readonly CaseFile $case,
        private readonly Collection $transactions,
    ) {
    }

    public function headings(): array
    {
        return [
            'case_id',
            'case_title',
            'transaction_id',
            'date',
            'label',
            'normalized_label',
            'amount',
            'type',
            'kind',
            'origin',
            'destination',
            'motif',
            'cheque_number',
            'anomaly_score',
            'rule_flags',
            'bank_account_id',
        ];
    }

    public function collection(): Collection
    {
        return $this->transactions->map(function ($tx) {
            return [
                $this->case->getKey(),
                $this->case->title,
                $tx->getKey(),
                optional($tx->date)->format('Y-m-d'),
                (string) $tx->label,
                (string) ($tx->normalized_label ?? ''),
                (float) $tx->amount,
                (string) ($tx->type ?? ''),
                (string) ($tx->kind ?? ''),
                (string) ($tx->origin ?? ''),
                (string) ($tx->destination ?? ''),
                (string) ($tx->motif ?? ''),
                (string) ($tx->cheque_number ?? ''),
                $tx->anomaly_score,
                is_array($tx->rule_flags) ? json_encode($tx->rule_flags, JSON_UNESCAPED_UNICODE) : '',
                $tx->bank_account_id,
            ];
        });
    }
}
