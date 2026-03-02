<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

/**
 * Delete confirmed OCR date-ghost transactions.
 * Criteria used per pair - the "ghost" is the one where the date encoded
 * INSIDE the label contradicts the stored operation date.
 */
$ghosts = [
    // Pair #14: -50€ DAB 22/05/22 09H55 — SAME machine/second timestamp in both.
    // id=123653 has op_date=28/05 but label says "22/05/22 09H55 ... 23.05" → ghost
    123653 => 'Pair14: DAB même horodatage 22/05/22 09H55, label=23.05 mais date=28/05',

    // Pair #20: -10.40€ FACTURE CARTE — label of B says "23.12" internally but op=28/12
    120794 => 'Pair20: label interne=23.12, date op=28/12 → OCR 3→8',

    // Pair #23: -5.68€ FACTURE CARTE — A (op=10/12) label says "19.12" internally → A is ghost
    120770 => 'Pair23: label interne=19.12, date op=10/12 → OCR 1→0 ou 9→0',

    // Pair #35: +2.30€ ESCOTA — A (op=30/05) label starts "8105" misread of "3105"=31/05; B has correct "3105" + op=31/05
    122367 => 'Pair35: label prefixe=8105 (misread de 3105), op=30/05 → ghost de 31/05',

    // Pair #41: +13.20€ NETTO GARDANNE — B (op=28/05) label says "23.05" internally, same DU 220522
    123652 => 'Pair41: label interne=23.05 DU 220522, date op=28/05 → OCR 3→8',

    // Pair #44: +30.96€ KLARNA SHOWROOM — A (op=24/08) label says "30-08" internally; B (op=30/08) is correct
    120432 => 'Pair44: label interne=30-08, date op=24/08, prefixe=80.08 → OCR 3→8',

    // Pair #45: +37.70€ TM MARJAC — B (op=28/05) label says "23.05" internally, same DU 220524
    120093 => 'Pair45: label interne=23.05 DU 220524, date op=28/05 → OCR 3→8',

    // Pair #49: +170€ VER SEPA Anthony Giordano — B (op=08/05) label says "03.05" internally
    123588 => 'Pair49: label interne=03.05, date op=08/05 → OCR 0→8',

    // Pair #43: +17.89€ BIO D AMEL — A (op=13/11) label says "15.11" internally; B (op=15/11) is correct
    123023 => 'Pair43: label interne=15.11, date op=13/11, prefixe=45.11 → ghost de 15/11',
];

echo "=== OCR ghost deletions ===\n\n";

$deleted = 0;
foreach ($ghosts as $id => $reason) {
    $row = DB::table('transactions')->where('id', $id)->first();
    if (!$row) {
        echo "  SKIP id=$id (not found)\n";
        continue;
    }
    echo "DELETE id=$id | {$row->date} | {$row->type} | {$row->amount}€\n";
    echo "  Raison: $reason\n";
    echo "  Label: " . substr($row->label, 0, 100) . "\n";
    DB::table('transactions')->where('id', $id)->delete();
    $deleted++;
}

echo "\nTotal supprimés: $deleted\n";

$total = DB::table('transactions')
    ->join('bank_accounts', 'bank_accounts.id', '=', 'transactions.bank_account_id')
    ->where('bank_accounts.case_id', 1)
    ->count();
echo "Transactions restantes: $total\n";
