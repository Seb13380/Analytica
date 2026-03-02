<?php
/**
 * Détecte et supprime les doublons de transactions haute valeur (crédits et débits)
 * basés sur : même bank_account_id + même date + même montant exact + même type.
 * Pour chaque groupe de doublons, conserve l'enregistrement avec l'id le plus bas (premier importé).
 *
 * Usage : docker compose exec app php tools/dedupe_high_value_credits.php [--dry-run]
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$dryRun = in_array('--dry-run', $argv ?? [], true) || !in_array('--execute', $argv ?? [], true);

echo $dryRun
    ? "=== MODE DRY-RUN (ajoutez --execute pour supprimer) ===\n\n"
    : "=== MODE EXÉCUTION — SUPPRESSION RÉELLE ===\n\n";

// Trouver les groupes avec plusieurs enregistrements identiques (même compte / date / montant / type)
$threshold = (float) config('analytica.import.high_value_threshold', 8000);

$groups = DB::select(<<<SQL
    SELECT
        bank_account_id,
        date::date          AS tx_date,
        ABS(amount)         AS abs_amount,
        type,
        COUNT(*)            AS cnt,
        MIN(id)             AS keep_id,
        ARRAY_AGG(id ORDER BY id) AS all_ids
    FROM transactions
    WHERE ABS(amount) >= ?
    GROUP BY bank_account_id, date::date, ABS(amount), type
    HAVING COUNT(*) > 1
    ORDER BY cnt DESC, abs_amount DESC
SQL, [$threshold]);

if (empty($groups)) {
    echo "Aucun doublon détecté pour les transactions >= {$threshold} €.\n";
    exit(0);
}

$totalDeleted = 0;

foreach ($groups as $g) {
    $allIds   = array_map('intval', str_getcsv(trim($g->all_ids, '{}')));
    $keepId   = (int) $g->keep_id;
    $deleteIds = array_filter($allIds, fn($id) => $id !== $keepId);

    // Vérification supplémentaire : les étiquettes sont-elles similaires ?
    $txs = DB::table('transactions')
        ->whereIn('id', $allIds)
        ->select('id', 'normalized_label', 'label', 'date', 'amount', 'bank_account_id')
        ->get();

    $labels = $txs->pluck('normalized_label')->map(fn($l) => mb_strtoupper(trim((string)$l)))->unique()->values();

    echo sprintf(
        "★ Doublon ×%d — compte #%d | %s | %.2f € | %s\n",
        $g->cnt,
        $g->bank_account_id,
        $g->tx_date,
        $g->abs_amount,
        $g->type
    );
    foreach ($txs as $tx) {
        $marker = ($tx->id === $keepId) ? '  [GARDER]' : '  [SUP]   ';
        echo sprintf("%s id=%d  %s\n", $marker, $tx->id, mb_substr(trim((string)$tx->normalized_label), 0, 90));
    }

    if (!$dryRun) {
        DB::table('transactions')->whereIn('id', $deleteIds)->delete();
        echo sprintf("  → %d ligne(s) supprimée(s).\n", count($deleteIds));
        $totalDeleted += count($deleteIds);
    } else {
        echo sprintf("  → (dry-run) %d ligne(s) seraient supprimées.\n", count($deleteIds));
    }
    echo "\n";
}

echo "Total " . ($dryRun ? 'à supprimer' : 'supprimé') . " : {$totalDeleted} transaction(s).\n";
