<?php
/**
 * Réparation définitive :
 *  - Compte chèques commun (RIB 660) : account #2 ← tous les relevés + transactions
 *  - Livret DEV (RIB 160)            : account #3 ← vide, en attente d'import
 */
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// ─────────────────────────────────────────────────────────────────────────────
// Compte chèques commun = account #2
// ─────────────────────────────────────────────────────────────────────────────
$cheques = \App\Models\BankAccount::find(2);
$cheques->forceFill([
    'bank_name'      => 'BNP',
    'account_type'   => 'joint',
    'account_holder' => 'Christian Giordano',
    'rib'            => '30004 00702 00002123116 60',
    'iban_masked'    => $cheques->iban_masked ?? null,
])->save();
echo "Compte #2 mis à jour (compte chèques commun)\n";

// ─────────────────────────────────────────────────────────────────────────────
// Livret DEV = account #3 → réinitialiser (vide)
// ─────────────────────────────────────────────────────────────────────────────
$livret = \App\Models\BankAccount::find(3);
$livret->forceFill([
    'bank_name'      => 'BNP',
    'account_type'   => 'savings',
    'account_holder' => 'Christian Giordano',
    'rib'            => '30004 00702 00075123861 60',
    'iban_masked'    => 'FR76 3000 4007 0200 0751 2386 160',
])->save();
echo "Compte #3 mis à jour (Livret DEV, vide)\n";

// ─────────────────────────────────────────────────────────────────────────────
// Déplacer tous les statements vers le compte chèques (#2)
// ─────────────────────────────────────────────────────────────────────────────
$moved = DB::table('statements')->whereIn('id', [1, 2])->update(['bank_account_id' => 2]);
echo "Statements déplacés vers #2 : {$moved}\n";

// ─────────────────────────────────────────────────────────────────────────────
// Consolider toutes les transactions sur le compte chèques (#2)
// Les transactions sur #3 doivent aller sur #2 (avec gestion doublons)
// ─────────────────────────────────────────────────────────────────────────────
$txIds3 = DB::table('transactions')->where('bank_account_id', 3)->pluck('id');
$moved3 = 0; $deduped3 = 0;
foreach ($txIds3 as $txId) {
    try {
        DB::table('transactions')->where('id', $txId)->update(['bank_account_id' => 2]);
        $moved3++;
    } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
        DB::table('transactions')->where('id', $txId)->delete();
        $deduped3++;
    }
}
echo "Transactions de #3 → #2 : {$moved3} déplacées, {$deduped3} doublons supprimés\n";

// ─────────────────────────────────────────────────────────────────────────────
// État final
// ─────────────────────────────────────────────────────────────────────────────
echo "\n=== ÉTAT FINAL ===\n";
foreach (\App\Models\BankAccount::orderBy('id')->get() as $acc) {
    $stmts = DB::table('statements')->where('bank_account_id', $acc->id)->count();
    $txs   = DB::table('transactions')->where('bank_account_id', $acc->id)->count();
    echo "#{$acc->id} {$acc->bank_name} | {$acc->account_type} | {$acc->account_holder} | {$acc->rib} | {$stmts} relevés | {$txs} tx\n";
}
echo "\nTerminé.\n";
