<?php
/**
 * Fusionne les bank_accounts ayant le même RIB (même compte physique).
 * Redirige toutes les transactions et statements du doublon vers le compte maître,
 * puis supprime l'entrée en double.
 */
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$accounts = \App\Models\BankAccount::all();

// Regrouper par RIB normalisé (enlever espaces)
$byRib = [];
foreach ($accounts as $acc) {
    $rib = preg_replace('/\s+/', '', (string)($acc->rib ?? ''));
    if ($rib === '') continue;
    $byRib[$rib][] = $acc;
}

foreach ($byRib as $rib => $group) {
    if (count($group) <= 1) continue;

    // Compte maître = le plus ancien (id le plus petit)
    usort($group, fn($a, $b) => $a->id - $b->id);
    $master = $group[0];
    $duplicates = array_slice($group, 1);

    echo "RIB {$rib}:\n";
    echo "  → Maître: compte #{$master->id}\n";

    foreach ($duplicates as $dup) {
        echo "  → Doublon: compte #{$dup->id} — fusion en cours...\n";

        // Rediriger statements
        $stmtCount = DB::table('statements')->where('bank_account_id', $dup->id)->count();
        DB::table('statements')->where('bank_account_id', $dup->id)->update(['bank_account_id' => $master->id]);
        echo "     {$stmtCount} relevé(s) redirigé(s)\n";

        // Rediriger transactions (en ignorant les doublons déjà présents sur le maître)
        // Tenter un update optimiste ; en cas de conflit de dédup, supprimer le doublon
        $txIds = DB::table('transactions')->where('bank_account_id', $dup->id)->pluck('id');
        $moved = 0; $deleted = 0;
        foreach ($txIds as $txId) {
            try {
                DB::table('transactions')->where('id', $txId)->update(['bank_account_id' => $master->id]);
                $moved++;
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                // Transaction identique déjà présente sur le maître → supprimer le doublon
                DB::table('transactions')->where('id', $txId)->delete();
                $deleted++;
            }
        }
        echo "     {$moved} transaction(s) déplacée(s), {$deleted} doublon(s) supprimé(s)\n";

        // Supprimer le doublon
        $dup->delete();
        echo "     Compte #{$dup->id} supprimé.\n";
    }
}

echo "\nÉtat final des comptes:\n";
foreach (\App\Models\BankAccount::orderBy('id')->get() as $acc) {
    $stmts = DB::table('statements')->where('bank_account_id', $acc->id)->count();
    $txs   = DB::table('transactions')->where('bank_account_id', $acc->id)->count();
    echo "  #{$acc->id} {$acc->bank_name} | {$acc->account_type} | {$acc->account_holder} | RIB:{$acc->rib} | {$stmts} relevés | {$txs} transactions\n";
}
echo "\nTerminé.\n";
