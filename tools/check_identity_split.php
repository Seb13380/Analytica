<?php

declare(strict_types=1);

use App\Models\Transaction;
use App\Services\Normalization;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$rows = Transaction::query()
    ->where(function ($query) {
        $query->where('normalized_label', 'like', '%GIORDANO%')
            ->orWhere('label', 'like', '%GIORDANO%')
            ->orWhere('normalized_label', 'like', '%NOVAK%')
            ->orWhere('label', 'like', '%NOVAK%');
    })
    ->get(['id', 'date', 'label', 'normalized_label', 'amount', 'type']);

$classify = static function (string $normalized): string {
    if ($normalized === '') {
        return 'AUTRE';
    }

    $hasGiordano = preg_match('/\bGIORDANO\b/u', $normalized) === 1;
    $hasNovak = preg_match('/\bNOVAK\b/u', $normalized) === 1;
    $hasLiliane = preg_match('/\bLILIANE\b/u', $normalized) === 1;
    $hasAnthonyNamed = preg_match('/\b(?:GIORDANO\s+ANTHONY|ANTHONY\s+GIORDANO)\b/u', $normalized) === 1;
    $hasEmilieNamed = preg_match('/\b(?:GIORDANO\s+EMILIE|EMILIE\s+GIORDANO|GIORDANO\s+EMILE|EMILE\s+GIORDANO)\b/u', $normalized) === 1;
    $hasChristianVariant =
        preg_match('/\b(?:CHRISTIAN|CHRISTAN|CHRESTIAN|CHRESTAN|CHRSTIAN|CHRSTAN)\b/u', $normalized) === 1
        || preg_match('/\bCHR\s*ST\s*AN\b/u', $normalized) === 1;
    $hasFemaleTitle = preg_match('/\b(MME|MADAME)\b/u', $normalized) === 1;
    $hasMaleTitle = preg_match('/\b(MR|MONSIEUR)\b/u', $normalized) === 1;

    $hasJointMarker =
        preg_match('/\bM\s*OU\s*MME\b/u', $normalized) === 1
        || preg_match('/\bM\s*ET\s*MME\b/u', $normalized) === 1
        || preg_match('/\b(MR|MONSIEUR)\b.*\b(MME|MADAME)\b/u', $normalized) === 1
        || preg_match('/\b(MME|MADAME)\b.*\b(MR|MONSIEUR)\b/u', $normalized) === 1;

    if ($hasAnthonyNamed) {
        return 'PERSONNE_ANTHONY_GIORDANO';
    }

    if ($hasEmilieNamed) {
        return 'PERSONNE_EMILIE_GIORDANO';
    }

    if ($hasJointMarker && $hasGiordano) {
        return 'COMPTE_COMMUN_GIORDANO';
    }

    if (($hasFemaleTitle && ($hasGiordano || $hasNovak)) || $hasLiliane || $hasNovak) {
        return 'PERSONNE_LILIANE_GIORDANO_NOVAK';
    }

    if (($hasMaleTitle && $hasGiordano) || ($hasChristianVariant && $hasGiordano)) {
        return 'PERSONNE_M_GIORDANO';
    }

    return 'AUTRE_GIORDANO';
};

$mapped = $rows->map(function (Transaction $tx) use ($classify): array {
    $normalized = (string) ($tx->normalized_label ?: Normalization::normalizeLabel((string) $tx->label));

    return [
        'id' => $tx->id,
        'date' => (string) $tx->date,
        'type' => (string) $tx->type,
        'amount' => (float) $tx->amount,
        'label' => (string) $tx->label,
        'key' => $classify($normalized),
    ];
});

$summary = $mapped
    ->groupBy('key')
    ->map(fn ($items) => count($items))
    ->sortDesc();

echo "=== Identity split summary ===\n";
foreach ($summary as $key => $count) {
    echo str_pad((string) $key, 38).' : '.$count.PHP_EOL;
}

echo "\n=== Samples (up to 3 per bucket) ===\n";
$sampleBuckets = $mapped->groupBy('key');
foreach ($sampleBuckets as $key => $items) {
    echo "\n[{$key}]\n";
    foreach ($items->take(3) as $item) {
        echo sprintf(
            "- #%d | %s | %s | %0.2f | %s\n",
            (int) $item['id'],
            (string) $item['date'],
            (string) $item['type'],
            (float) $item['amount'],
            (string) $item['label']
        );
    }
}
