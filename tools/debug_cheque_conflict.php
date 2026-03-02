<?php
require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$statementId = (int)($argv[1] ?? 11);
$statement = \App\Models\Statement::find($statementId);
if (!$statement) {
    echo "Statement not found\n";
    exit(1);
}

$text = (string)($statement->extracted_text ?? '');
$importer = app(\App\Services\StatementImportService::class);

$raw = $importer->parseTransactionsFromText($text);
$final = $importer->finalizeTransactions($raw, $text, 45);

$conflicts = [];
foreach ($final as $tx) {
    if (($tx['kind'] ?? '') !== 'cheque') {
        continue;
    }
    $num = trim((string)($tx['cheque_number'] ?? ''));
    if ($num === '') {
        continue;
    }
    $key = ($tx['date'] ?? '').'|'.($tx['type'] ?? '').'|'.$num;
    $conflicts[$key][] = [
        'amount' => $tx['amount'] ?? null,
        'label' => $tx['label'] ?? '',
        'flags' => $tx['meta']['quality_flags'] ?? [],
    ];
}

foreach ($conflicts as $key => $rows) {
    if (count($rows) > 1) {
        echo "CONFLICT $key\n";
        foreach ($rows as $r) {
            echo '  - '.$r['amount'].' | '.substr((string)$r['label'], 0, 120).' | flags='.json_encode($r['flags'], JSON_UNESCAPED_UNICODE)."\n";
        }
    }
}

echo "Total raw=".count($raw)." final=".count($final)."\n";
