<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\Normalization;

// Find MILLIET transactions
$rows = DB::table('transactions as t')
    ->join('bank_accounts as ba', 'ba.id', '=', 't.bank_account_id')
    ->where('ba.case_id', 1)
    ->where(function($q) {
        $q->whereRaw("UPPER(t.label) LIKE '%MILLIET%'")
          ->orWhereRaw("UPPER(t.normalized_label) LIKE '%MILLIET%'");
    })
    ->select('t.id', 't.date', 't.amount', 't.type', 't.label', 't.normalized_label')
    ->orderBy('t.date')
    ->get();

echo "=== Transactions MILLIET ===\n";
foreach ($rows as $r) {
    $clean = Normalization::cleanLabel($r->label);
    $norm  = Normalization::normalizeLabel($r->label);
    echo "id={$r->id} | {$r->date} | {$r->type} | " . number_format(abs($r->amount), 2) . "€\n";
    echo "  label DB:   " . substr($r->label, 0, 120) . "\n";
    echo "  label NEW:  " . substr($clean, 0, 120) . "\n";
    echo "  normalized: " . substr($norm, 0, 100) . "\n";

    // Test cluster matching
    $clusters = config('analytica.beneficiary_alias_clusters', []);
    foreach ($clusters as $cluster) {
        $tokens = collect((array)($cluster['tokens'] ?? []))
            ->map(fn($v) => Normalization::normalizeLabel((string)$v))
            ->filter()->unique()->values()->all();
        $matches = 0;
        foreach ($tokens as $t) {
            if (in_array($t, explode(' ', $norm)) || str_contains($norm, $t)) $matches++;
        }
        if ($matches >= max(1, (int)($cluster['min_match'] ?? 1))) {
            echo "  ✓ Cluster: " . $cluster['key'] . " → " . $cluster['label'] . "\n";
        }
    }
    echo "\n";
}
