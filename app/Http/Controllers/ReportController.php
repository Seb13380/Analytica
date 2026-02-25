<?php

namespace App\Http\Controllers;

use App\Exports\CaseTransactionsExport;
use App\Models\CaseFile;
use App\Models\Report;
use App\Models\Transaction;
use App\Services\EncryptedFileStorage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;

class ReportController extends Controller
{
    public function generate(Request $request, CaseFile $case, EncryptedFileStorage $storage)
    {
        Gate::authorize('update', $case);

        $case->load(['bankAccounts', 'bankAccounts.statements']);

        $format = strtolower((string) $request->input('format', 'pdf'));

        $data = $this->buildReportData($case);

        $latestVersion = (int) (Report::query()->where('case_id', $case->getKey())->max('version') ?? 0);
        $version = $latestVersion + 1;

        if (in_array($format, ['xlsx', 'excel'], true)) {
            $filename = sprintf('analytica-report-case-%d-v%d.xlsx', $case->getKey(), $version);
            $mimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            $bytes = Excel::raw(new CaseTransactionsExport($case, $data['allTransactionsForExport']), ExcelFormat::XLSX);
        } else {
            $filename = sprintf('analytica-report-case-%d-v%d.pdf', $case->getKey(), $version);
            $mimeType = 'application/pdf';
            $bytes = $this->buildPdfBytes($case, $data);
        }

        $stored = $storage->storeEncryptedBytes(
            caseFile: $case,
            kind: 'reports',
            extension: 'bin',
            plaintext: $bytes,
            originalFilename: $filename,
            mimeType: $mimeType,
            sizeBytes: strlen($bytes),
        );

        Report::create([
            'case_id' => $case->getKey(),
            'file_path' => $stored['file_path'],
            'hash_integrity' => $stored['hash_integrity'],
            'original_filename' => $stored['original_filename'],
            'mime_type' => $stored['mime_type'],
            'size_bytes' => $stored['size_bytes'],
            'encryption_alg' => $stored['encryption_alg'],
            'encryption_meta' => $stored['encryption_meta'],
            'generated_at' => now(),
            'version' => $version,
        ]);

        return response()->streamDownload(function () use ($bytes) {
            echo $bytes;
        }, $filename, [
            'Content-Type' => $mimeType,
        ]);
    }

    public function download(Report $report, EncryptedFileStorage $storage)
    {
        $case = $report->caseFile;
        abort_unless($case !== null, 404);

        Gate::authorize('view', $case);

        $bytes = $storage->getDecryptedBytes($report->file_path, $report->encryption_meta ?? []);
        $filename = $report->original_filename ?: sprintf('analytica-report-%d', $report->getKey());
        $mimeType = $report->mime_type ?: 'application/octet-stream';

        return response()->streamDownload(function () use ($bytes) {
            echo $bytes;
        }, $filename, [
            'Content-Type' => $mimeType,
        ]);
    }

    private function buildPdfBytes(CaseFile $case, array $data): string
    {
        $pdf = Pdf::loadView('reports.case', [
            'case' => $case,
            'totalTransactions' => $data['totalTransactions'],
            'totalFlagged' => $data['totalFlagged'],
            'totalFlaggedAmount' => $data['totalFlaggedAmount'],
            'totalDebit' => $data['totalDebit'],
            'totalCredit' => $data['totalCredit'],
            'netAmount' => $data['netAmount'],
            'topBeneficiaries' => $data['topBeneficiaries'],
            'flaggedTransactions' => $data['flaggedTransactions'],
            'allTransactionsSample' => $data['allTransactionsSample'],
            'statementStats' => $data['statementStats'],
        ])->setPaper('a4');

        return $pdf->output();
    }

    private function buildReportData(CaseFile $case): array
    {
        $accountIds = $case->bankAccounts->pluck('id');

        $txQuery = Transaction::query()
            ->whereIn('bank_account_id', $accountIds);

        $totalTransactions = (clone $txQuery)->count();

        $flaggedTransactions = (clone $txQuery)
            ->where('anomaly_score', '>=', 30)
            ->orderByDesc('anomaly_score')
            ->orderByDesc('date')
            ->limit(200)
            ->get();

        $allTransactionsSample = (clone $txQuery)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $allTransactionsForExport = (clone $txQuery)
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $totalFlagged = $flaggedTransactions->count();
        $totalFlaggedAmount = (float) $flaggedTransactions->sum(fn ($t) => abs((float) $t->amount));
        $totalDebit = (float) (clone $txQuery)->where('type', 'debit')->sum('amount');
        $totalCredit = (float) (clone $txQuery)->where('type', 'credit')->sum('amount');
        $netAmount = abs($totalCredit) - abs($totalDebit);

        $topBeneficiaries = (clone $txQuery)
            ->get()
            ->groupBy(fn ($t) => (string) ($t->normalized_label ?? ''))
            ->map(fn ($txs) => $txs->sum(fn ($t) => abs((float) $t->amount)))
            ->sortDesc()
            ->take(10);

        $statements = $case->bankAccounts->flatMap(fn ($account) => $account->statements);
        $statementStats = [
            'total' => $statements->count(),
            'completed' => $statements->where('import_status', 'completed')->count(),
            'failed' => $statements->where('import_status', 'failed')->count(),
            'processing' => $statements->where('import_status', 'processing')->count(),
            'transactions_imported' => (int) $statements->sum(fn ($s) => (int) ($s->transactions_imported ?? 0)),
            'ocr_used' => $statements->where('ocr_used', true)->count(),
        ];

        return [
            'totalTransactions' => $totalTransactions,
            'totalFlagged' => $totalFlagged,
            'totalFlaggedAmount' => round($totalFlaggedAmount, 2),
            'totalDebit' => round(abs($totalDebit), 2),
            'totalCredit' => round(abs($totalCredit), 2),
            'netAmount' => round($netAmount, 2),
            'topBeneficiaries' => $topBeneficiaries,
            'flaggedTransactions' => $flaggedTransactions,
            'allTransactionsSample' => $allTransactionsSample,
            'allTransactionsForExport' => $allTransactionsForExport,
            'statementStats' => $statementStats,
        ];
    }
}
