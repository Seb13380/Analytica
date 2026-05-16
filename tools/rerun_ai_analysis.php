<?php
/**
 * Re-run AI analysis for a case and store the result directly in the DB.
 * Usage: php tools/rerun_ai_analysis.php [case_id]
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CaseFile;
use App\Models\Transaction;
use App\Services\AiAssistant;

$caseId = (int) ($argv[1] ?? 1);
$case = CaseFile::with(['bankAccounts.statements'])->findOrFail($caseId);

echo "=== AI Re-analysis for case #{$caseId}: {$case->title}\n";
echo "Death date: " . ($case->death_date?->format('Y-m-d') ?? '—') . "\n";

$accountIds = $case->bankAccounts()->pluck('id');
$max = (int) config('analytica.ai.max_transactions', 300);
$highValueThreshold = 20000.0;

$cols = ['id', 'date', 'amount', 'type', 'kind', 'origin', 'destination', 'motif', 'cheque_number', 'label', 'normalized_label', 'anomaly_score'];

$recentRows = Transaction::query()
    ->whereIn('bank_account_id', $accountIds)
    ->orderByDesc('date')
    ->orderByDesc('id')
    ->limit($max)
    ->get($cols);

$recentIds = $recentRows->pluck('id');
$highValueRows = Transaction::query()
    ->whereIn('bank_account_id', $accountIds)
    ->whereRaw('ABS(amount) >= ?', [$highValueThreshold])
    ->whereNotIn('id', $recentIds)
    ->get($cols);

$transactions = $recentRows->concat($highValueRows)
    ->map(fn ($t) => [
        'date'             => optional($t->date)->format('Y-m-d'),
        'amount'           => (float) $t->amount,
        'type'             => (string) $t->type,
        'kind'             => $t->kind,
        'origin'           => $t->origin,
        'destination'      => $t->destination,
        'motif'            => $t->motif,
        'cheque_number'    => $t->cheque_number,
        'label'            => $t->label,
        'normalized_label' => $t->normalized_label,
        'anomaly_score'    => $t->anomaly_score,
    ])
    ->values()
    ->all();

echo "Transactions sent to AI: " . count($transactions) . " (" . count($recentRows) . " récentes + " . count($highValueRows) . " hauts montants)\n";

// Show cheque > 7000 summary
$bigCheques = array_filter($transactions, fn($t) => ($t['kind'] ?? '') === 'cheque' && abs($t['amount']) >= 7000);
echo "Chèques >= 7000€ envoyés: " . count($bigCheques) . "\n";
foreach ($bigCheques as $c) {
    echo "  {$c['date']} | {$c['amount']} | {$c['label']}\n";
}

$context = [
    'transactions_count' => count($transactions),
    'transactions'       => $transactions,
    'recent_statements'  => $case->bankAccounts->flatMap(fn ($a) => $a->statements)
        ->sortByDesc('created_at')->take(10)
        ->map(fn ($s) => [
            'filename'               => $s->original_filename,
            'status'                 => $s->import_status,
            'transactions_imported'  => $s->transactions_imported,
            'ocr_used'               => (bool) $s->ocr_used,
            'message'                => $s->import_error,
            'created_at'             => optional($s->created_at)->toIso8601String(),
        ])->values()->all(),
];

$assistant = app(AiAssistant::class);
$prompt = 'Analyse forensique complète du dossier successoral GIORDANO Christian. Respecte scrupuleusement les règles de seuil (chèques >= 7000€ seulement, virements >= 5000€ seulement, retraits espèces uniquement ±60j du décès, Maître Millet = remboursement plus-value normal).';

echo "\nCalling AI...\n";
try {
    $result = $assistant->analyzeCase($case, $context, $prompt);

    $case->forceFill([
        'ai_last_prompt'  => $prompt,
        'ai_last_result'  => $result,
        'ai_last_error'   => null,
        'ai_last_ran_at'  => now(),
    ])->save();

    echo "\n=== SUMMARY ===\n";
    echo wordwrap((string) ($result['summary'] ?? '—'), 100, "\n", true) . "\n";

    echo "\n=== SUSPICIOUS (" . count($result['suspicious'] ?? []) . ") ===\n";
    foreach ((array) ($result['suspicious'] ?? []) as $s) {
        echo "— $s\n";
    }

    echo "\nResult saved to DB. Reload the case page to see the updated analysis.\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
