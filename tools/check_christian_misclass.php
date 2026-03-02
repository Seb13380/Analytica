<?php

require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Transaction;
use App\Services\Normalization;

$rows = Transaction::query()
    ->where(function ($q) {
        $q->where('label', 'ilike', '%CHRIST%')
          ->orWhere('normalized_label', 'ilike', '%CHRIST%')
          ->orWhere('label', 'ilike', '%CHR:ST%')
          ->orWhere('normalized_label', 'ilike', '%CHR:ST%');
    })
    ->where(function ($q) {
        $q->where('label', 'ilike', '%GIORD%')
          ->orWhere('normalized_label', 'ilike', '%GIORD%')
          ->orWhere('label', 'ilike', '%GORDANO%')
          ->orWhere('normalized_label', 'ilike', '%GORDANO%');
    })
    ->get(['id', 'date', 'label', 'normalized_label', 'amount', 'type']);

$classify = static function (string $normalized): string {
    if ($normalized === '') {
        return 'INCONNU';
    }

    $hasGiordano = preg_match('/\bGI?ORDANO\b/u', $normalized) === 1;
    $hasNovak = preg_match('/\bNOVAK\b/u', $normalized) === 1;
    $hasLiliane = preg_match('/\bLILIANE\b/u', $normalized) === 1;
    $hasAnthonyNamed = preg_match('/\b(?:GI?ORDANO\s+ANTHONY|ANTHONY\s+GI?ORDANO)\b/u', $normalized) === 1;
    $hasEmilieNamed = preg_match('/\b(?:GI?ORDANO\s+EMILIE|EMILIE\s+GI?ORDANO|GI?ORDANO\s+EMILE|EMILE\s+GI?ORDANO)\b/u', $normalized) === 1;
    $hasChristianVariant =
        preg_match('/\b(?:CHRISTIAN|CHRISTAN|CHRESTIAN|CHRESTAN|CHRSTIAN|CHRSTAN)\b/u', $normalized) === 1
        || preg_match('/\bCHR[:\'"\s]*ST[:\'"\s]*AN\b/u', $normalized) === 1
        || preg_match('/\bCHR(?:I|E)?ST(?:I|E)?AN\b/u', $normalized) === 1
        || preg_match('/\bCHR[:\'"\s]*STE?AN\b/u', $normalized) === 1
        || preg_match('/\bCHRIST\b/u', $normalized) === 1;
    $hasFemaleTitle = preg_match('/\b(MME|MADAME)\b/u', $normalized) === 1;
    $hasMaleTitle = preg_match('/\b(MR|MONSIEUR)\b/u', $normalized) === 1;

    $hasJointMarker =
        preg_match('/\bM\s*OU\s*MME\b/u', $normalized) === 1
        || preg_match('/\bM\s*ET\s*MME\b/u', $normalized) === 1
        || preg_match('/\bM\b.{0,24}\bO[UÙ]\b.{0,12}\bMME\b/u', $normalized) === 1
        || preg_match('/\bMME\b.{0,24}\bO[UÙ]\b.{0,12}\bM\b/u', $normalized) === 1
        || preg_match('/\b(MR|MONSIEUR)\b.*\b(MME|MADAME)\b/u', $normalized) === 1
        || preg_match('/\b(MME|MADAME)\b.*\b(MR|MONSIEUR)\b/u', $normalized) === 1
        || ($hasChristianVariant && $hasFemaleTitle && $hasGiordano);

    if ($hasAnthonyNamed) {
        return 'PERSONNE_ANTHONY_GIORDANO';
    }

    if ($hasEmilieNamed) {
        return 'PERSONNE_EMILIE_GIORDANO';
    }

    if ($hasJointMarker && $hasGiordano) {
        return 'COMPTE_COMMUN_GIORDANO';
    }

    if (($hasMaleTitle && $hasGiordano) || ($hasChristianVariant && $hasGiordano)) {
        return 'PERSONNE_M_GIORDANO';
    }

    if (($hasFemaleTitle && ($hasGiordano || $hasNovak)) || $hasLiliane || $hasNovak) {
        return 'PERSONNE_LILIANE_GIORDANO_NOVAK';
    }

    return 'AUTRE';
};

$misclassified = [];

foreach ($rows as $tx) {
    $normalized = (string) ($tx->normalized_label ?: Normalization::normalizeLabel((string) $tx->label));
    $key = $classify($normalized);

    if ($key === 'PERSONNE_LILIANE_GIORDANO_NOVAK') {
        $misclassified[] = [
            'id' => $tx->id,
            'date' => (string) $tx->date,
            'label' => (string) $tx->label,
            'key' => $key,
        ];
    }
}

echo "Rows checked: ".count($rows)."\n";
echo "Christian-like rows misclassified as Mme: ".count($misclassified)."\n\n";

foreach (array_slice($misclassified, 0, 15) as $row) {
    echo '#'.$row['id'].' | '.$row['date'].' | '.$row['key']."\n";
    echo '  '.mb_substr($row['label'], 0, 220)."\n";
}
