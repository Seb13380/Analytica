<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\Normalization;

/**
 * Retroactively apply OCR colon/apostrophe → 'i' repairs to all labels.
 * Processes transactions for case 1.
 */

$batchSize = 500;
$offset = 0;
$totalFixed = 0;
$totalChecked = 0;

echo "=== Retroactive OCR 'i' repair (colon/apostrophe within words) ===\n\n";

do {
    $rows = DB::table('transactions as t')
        ->join('bank_accounts as ba', 'ba.id', '=', 't.bank_account_id')
        ->where('ba.case_id', 1)
        ->select('t.id', 't.label', 't.normalized_label')
        ->orderBy('t.id')
        ->offset($offset)
        ->limit($batchSize)
        ->get();

    foreach ($rows as $r) {
        $totalChecked++;
        $newLabel      = Normalization::cleanLabel($r->label);
        $newNormalized = Normalization::normalizeLabel($r->label);

        if ($newLabel !== $r->label || $newNormalized !== $r->normalized_label) {
            // Show a few examples
            if ($totalFixed < 20) {
                echo "id={$r->id}\n";
                echo "  BEFORE: " . substr($r->label, 0, 100) . "\n";
                echo "  AFTER:  " . substr($newLabel, 0, 100) . "\n";
            }

            DB::table('transactions')->where('id', $r->id)->update([
                'label'            => $newLabel,
                'normalized_label' => $newNormalized,
                'updated_at'       => now(),
            ]);
            $totalFixed++;
        }
    }

    $offset += $batchSize;
} while (count($rows) === $batchSize);

echo "\n=== Done ===\n";
echo "Checked: $totalChecked transactions\n";
echo "Fixed:   $totalFixed transactions\n";
