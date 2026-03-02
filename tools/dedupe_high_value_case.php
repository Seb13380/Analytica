<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$caseId = (int) ($argv[1] ?? 1);
$threshold = (float) ($argv[2] ?? 20000);

$rows = \App\Models\Transaction::query()
    ->join('bank_accounts', 'bank_accounts.id', '=', 'transactions.bank_account_id')
    ->where('bank_accounts.case_id', $caseId)
    ->whereRaw('ABS(transactions.amount) >= ?', [$threshold])
    ->orderBy('transactions.bank_account_id')
    ->orderBy('transactions.date')
    ->orderBy('transactions.id')
    ->get([
        'transactions.id',
        'transactions.bank_account_id',
        'transactions.date',
        'transactions.amount',
        'transactions.type',
        'transactions.kind',
        'transactions.normalized_label',
        'transactions.meta',
    ]);

$canonicalize = static function (string $label): string {
    $label = mb_strtoupper($label);
    $label = preg_replace('/\b(REFDO|REFBEN|REF|EMETTEUR|MDT|IBAN|BIC|RIB|BEN|MOT|MOTIF|SINISTRE|SINSTRE)\b/u', ' ', $label) ?? $label;
    $label = preg_replace('/\b[A-Z0-9]{8,}\b/u', ' ', $label) ?? $label;
    $label = preg_replace('/\d+/u', ' ', $label) ?? $label;
    $label = strtr($label, ['É'=>'E','È'=>'E','Ê'=>'E','Ë'=>'E','À'=>'A','Â'=>'A','Ä'=>'A','Î'=>'I','Ï'=>'I','Ô'=>'O','Ö'=>'O','Ù'=>'U','Û'=>'U','Ü'=>'U','Ç'=>'C']);
    $label = preg_replace('/\s+/u', ' ', $label) ?? $label;

    return trim($label);
};

$groups = [];
$groupsBySemantic = [];
foreach ($rows as $row) {
    $date = $row->date ? \Carbon\Carbon::parse($row->date)->format('m-d') : '';
    $key = implode('|', [
        (string) $row->bank_account_id,
        $date,
        number_format(abs((float) $row->amount), 2, '.', ''),
        (string) $row->type,
        (string) $row->kind,
    ]);
    $groups[$key][] = $row;

    $semanticKey = implode('|', [
        (string) $row->bank_account_id,
        number_format(abs((float) $row->amount), 2, '.', ''),
        (string) $row->type,
        (string) $row->kind,
        $canonicalize((string) $row->normalized_label),
    ]);
    $groupsBySemantic[$semanticKey][] = $row;
}

$isSimilar = static function (string $left, string $right) use ($canonicalize): bool {
    $left = $canonicalize($left);
    $right = $canonicalize($right);

    if ($left === '' || $right === '') {
        return false;
    }

    if ($left === $right) {
        return true;
    }

    $minLen = min(mb_strlen($left), mb_strlen($right));
    if ($minLen >= 18 && (str_contains($left, $right) || str_contains($right, $left))) {
        return true;
    }

    $distance = levenshtein(mb_substr($left, 0, 255), mb_substr($right, 0, 255));
    $ratio = $distance / max(mb_strlen($left), mb_strlen($right), 1);

    return $ratio <= 0.28;
};

$score = static function ($row): int {
    $value = 0;
    $meta = is_array($row->meta) ? $row->meta : [];
    $flags = is_array($meta['quality_flags'] ?? null) ? $meta['quality_flags'] : [];

    $value += (int) ($meta['confidence'] ?? 0);
    if (in_array('outside_statement_period', $flags, true)) {
        $value -= 25;
    }

    if ($row->date) {
        $value += (int) \Carbon\Carbon::parse($row->date)->format('Y');
    }

    return $value;
};

$toDelete = [];
$examples = [];

foreach ($groups as $items) {
    if (count($items) <= 1) {
        continue;
    }

    usort($items, static function ($a, $b) use ($score) {
        return $score($b) <=> $score($a);
    });

    $kept = [];
    foreach ($items as $item) {
        $label = (string) ($item->normalized_label ?? '');
        $matched = false;

        foreach ($kept as $k) {
            $kLabel = (string) ($k->normalized_label ?? '');
            if ($isSimilar($label, $kLabel)) {
                $toDelete[] = (int) $item->id;
                if (count($examples) < 20) {
                    $examples[] = [
                        'delete_id' => (int) $item->id,
                        'keep_id' => (int) $k->id,
                        'amount' => (float) $item->amount,
                        'date' => (string) $item->date,
                        'label' => mb_substr((string) $item->normalized_label, 0, 120),
                    ];
                }
                $matched = true;
                break;
            }
        }

        if (!$matched) {
            $kept[] = $item;
        }
    }
}

foreach ($groupsBySemantic as $items) {
    if (count($items) <= 1) {
        continue;
    }

    usort($items, static function ($a, $b) use ($score) {
        return $score($b) <=> $score($a);
    });

    $kept = [];
    foreach ($items as $item) {
        $label = (string) ($item->normalized_label ?? '');
        $matched = false;

        foreach ($kept as $k) {
            $kLabel = (string) ($k->normalized_label ?? '');
            if ($isSimilar($label, $kLabel)) {
                $toDelete[] = (int) $item->id;
                if (count($examples) < 30) {
                    $examples[] = [
                        'delete_id' => (int) $item->id,
                        'keep_id' => (int) $k->id,
                        'date' => (string) $item->date,
                        'amount' => (float) $item->amount,
                        'label' => mb_substr((string) $item->normalized_label, 0, 120),
                    ];
                }
                $matched = true;
                break;
            }
        }

        if (!$matched) {
            $kept[] = $item;
        }
    }
}

$toDelete = array_values(array_unique($toDelete));

if ($toDelete !== []) {
    \App\Models\Transaction::query()->whereIn('id', $toDelete)->delete();
}

echo 'case=' . $caseId . PHP_EOL;
echo 'threshold=' . $threshold . PHP_EOL;
echo 'candidates=' . count($rows) . PHP_EOL;
echo 'deleted=' . count($toDelete) . PHP_EOL;

foreach ($examples as $example) {
    echo sprintf(
        "delete_id=%d keep_id=%d date=%s amount=%.2f label=%s\n",
        $example['delete_id'],
        $example['keep_id'],
        $example['date'],
        $example['amount'],
        $example['label']
    );
}
