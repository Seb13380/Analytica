<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make('Illuminate\\Contracts\\Console\\Kernel');
$kernel->bootstrap();

$statementId = isset($argv[1]) ? (int) $argv[1] : 0;
if ($statementId <= 0) {
    fwrite(STDERR, "Missing statement id\n");
    exit(1);
}

$statement = App\Models\Statement::findOrFail($statementId);
$importer = app(App\Services\StatementImportService::class);
$text = (string) ($statement->extracted_text ?? '');
$defaultYear = (int) now()->format('Y');
$txs = $importer->parseTransactionsFromText($text, $defaultYear);

echo 'parsed='.count($txs).PHP_EOL;

foreach ($txs as $i => $tx) {
    try {
        App\Models\Transaction::create([
            'bank_account_id' => $statement->bank_account_id,
            'date' => $tx['date'],
            'label' => $tx['label'],
            'normalized_label' => $tx['normalized_label'],
            'amount' => $tx['amount'],
            'type' => $tx['type'],
            'balance_after' => $tx['balance_after'],
            'beneficiary_detected' => $tx['beneficiary_detected'],
            'rule_flags' => $tx['rule_flags'],
            'kind' => $tx['kind'] ?? null,
            'origin' => $tx['origin'] ?? null,
            'destination' => $tx['destination'] ?? null,
            'motif' => $tx['motif'] ?? null,
            'cheque_number' => $tx['cheque_number'] ?? null,
            'meta' => $tx['meta'] ?? null,
        ]);

        echo 'inserted index '.$i.PHP_EOL;
    } catch (Throwable $e) {
        echo 'error index '.$i.' => '.$e->getMessage().PHP_EOL;
    }
}
