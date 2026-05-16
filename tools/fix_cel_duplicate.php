<?php
// Fix: delete the wrong-year CEL transaction (id=119064, date=2023-07-21)
// which is an OCR year-attribution error - the real transaction is 2025-07-21 (id=121339)
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$id = 119064;
$tx = App\Models\Transaction::find($id);
if (!$tx) {
    echo "Transaction id=$id introuvable.\n";
    exit(1);
}

echo "Transaction à supprimer:\n";
echo "  id=$tx->id | date=$tx->date | amount=$tx->amount | label=" . substr($tx->normalized_label, 0, 100) . "\n";

$tx->delete();
echo "Supprimée.\n";

// Verify the remaining one
$remaining = App\Models\Transaction::query()
    ->whereRaw('ABS(amount) = 18000')
    ->where('type', 'debit')
    ->where('normalized_label', 'LIKE', '%CEL%')
    ->get(['id','date','amount','normalized_label']);

echo "\nRestant en base:\n";
foreach ($remaining as $r) {
    echo "  id=$r->id | date=$r->date | label=" . substr($r->normalized_label, 0, 100) . "\n";
}
