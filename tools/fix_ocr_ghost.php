<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// The OCR misread "23" as "28" in the date for this Hyundai Tucson transaction
// Real date: 2025-05-23 (confirmed on PDF). The 2025-05-28 entry is a ghost.
$bad_id = 121194;

$row = DB::table('transactions')->where('id', $bad_id)->first();
if (!$row) {
    echo "Transaction $bad_id not found.\n";
    exit;
}

echo "Will delete:\n";
echo "  id={$row->id} | {$row->date} | {$row->amount} | " . substr($row->label, 0, 100) . "\n\n";

DB::table('transactions')->where('id', $bad_id)->delete();
echo "Deleted transaction $bad_id (OCR ghost: 28/05 misread of 23/05).\n";

// Confirm remaining
$remaining = DB::table('transactions as t')
    ->join('bank_accounts as ba', 'ba.id', '=', 't.bank_account_id')
    ->where('ba.case_id', 1)
    ->where('t.amount', 23496.00)
    ->select('t.id', 't.date', 't.amount', 't.label')
    ->get();
echo "\nRemaining 23496€ transactions:\n";
foreach ($remaining as $r) {
    echo "  id={$r->id} | {$r->date} | " . substr($r->label, 0, 80) . "\n";
}
