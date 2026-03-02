<?php
/**
 * Détecte les doublons avec décalage d'année OCR :
 * même bank_account_id + même jour/mois + même montant exact + même type
 * mais années différentes (ex. 2022 vs 2025).
 * Usage : docker compose exec app php tools/find_year_shift_dupes.php [--execute]
 */
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$dryRun = !in_array('--execute', $argv ?? [], true);
echo $dryRun ? "=== DRY-RUN ===\n\n" : "=== EXÉCUTION ===\n\n";

// Find rows sharing same account + same MM-DD + same |amount| + same type, but different years
$groups = DB::select(<<<SQL
    SELECT
        bank_account_id,
        to_char(date, 'MM-DD')   AS mmdd,
        ABS(amount)              AS abs_amount,
        type,
        COUNT(*)                 AS cnt,
        MIN(id)                  AS keep_id,
        ARRAY_AGG(id ORDER BY id)            AS all_ids,
        ARRAY_AGG(date::date ORDER BY id)    AS all_dates,
        ARRAY_AGG(LEFT(normalized_label,80) ORDER BY id) AS all_labels
    FROM transactions
    WHERE ABS(amount) >= 8000
    GROUP BY bank_account_id, to_char(date, 'MM-DD'), ABS(amount), type
    HAVING COUNT(DISTINCT EXTRACT(YEAR FROM date)) > 1
       AND COUNT(*) > 1
    ORDER BY cnt DESC, abs_amount DESC
SQL);

if (empty($groups)) {
    echo "Aucun doublon avec décalage d'année détecté.\n";
    exit(0);
}

$totalDeleted = 0;

foreach ($groups as $g) {
    $allIds    = array_map('intval', str_getcsv(trim($g->all_ids, '{}')));
    $allDates  = str_getcsv(trim($g->all_dates, '{}'));
    $allLabels = str_getcsv(trim($g->all_labels, '{}'));
    $keepId    = (int) $g->keep_id;

    echo sprintf("★ Doublon décalé ×%d — compte #%d | -%s- | %.2f € | %s\n",
        $g->cnt, $g->bank_account_id, $g->mmdd, $g->abs_amount, $g->type);

    foreach ($allIds as $idx => $id) {
        $marker = ($id === $keepId) ? '[GARDER]' : '[SUP]   ';
        echo sprintf("  %s id=%d  %s  %s\n", $marker, $id, $allDates[$idx] ?? '?', substr($allLabels[$idx] ?? '', 0, 70));
    }

    // Extra safety: verify labels are similar before deleting
    $deleteIds = array_filter($allIds, fn($id) => $id !== $keepId);

    // Compute label similarity between keep and each to-delete
    $keepLabel = '';
    foreach ($allIds as $idx => $id) {
        if ($id === $keepId) { $keepLabel = mb_strtoupper($allLabels[$idx] ?? ''); break; }
    }
    $safeDel = [];
    foreach ($deleteIds as $idx => $id) {
        $label = mb_strtoupper($allLabels[array_search($id, $allIds)] ?? '');
        // Strip leading day.month numeric + numbers + refs before comparing
        $norm = fn(string $s): string => trim(preg_replace(['/^\d{1,2}[\.\-]\d{2}\s+/u', '/\b\d+\b/u', '/\s+/u'], ['', ' ', ' '], $s));
        $lk = $norm($keepLabel); $ld = $norm($label);
        $maxLen = max(mb_strlen($lk), mb_strlen($ld), 1);
        $dist = levenshtein(mb_substr($lk, 0, 255), mb_substr($ld, 0, 255));
        $sim = 1 - ($dist / $maxLen);
        echo sprintf("    → similarité avec keep : %.0f%%\n", $sim * 100);
        if ($sim >= 0.40) {
            $safeDel[] = $id;
        } else {
            echo "    ⚠ similarité trop faible — ignoré (vérifier manuellement)\n";
        }
    }

    if (!empty($safeDel)) {
        if (!$dryRun) {
            DB::table('transactions')->whereIn('id', $safeDel)->delete();
            echo sprintf("    → %d supprimé(s).\n", count($safeDel));
            $totalDeleted += count($safeDel);
        } else {
            echo sprintf("    → (dry-run) %d seraient supprimés.\n", count($safeDel));
        }
    }
    echo "\n";
}

echo "Total " . ($dryRun ? 'à supprimer' : 'supprimé') . " : {$totalDeleted}\n";
