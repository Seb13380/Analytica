<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$statementId = (int) ($argv[1] ?? 0);
if ($statementId <= 0) {
    fwrite(STDERR, "Usage: php tools/diag_statement_parse.php <statementId>\n");
    exit(1);
}

$statement = App\Models\Statement::find($statementId);
if (!$statement) {
    fwrite(STDERR, "Statement not found: {$statementId}\n");
    exit(2);
}

$importer = app(App\Services\StatementImportService::class);
$text = (string) ($statement->extracted_text ?? '');
$defaultYear = (int) now()->format('Y');

$raw = $importer->parseTransactionsFromText($text, $defaultYear);
$final = $importer->finalizeTransactions($raw, $text, (int) config('analytica.import.min_confidence', 45));

echo 'statement='.$statementId.PHP_EOL;
echo 'raw_count='.count($raw).PHP_EOL;
echo 'final_count='.count($final).PHP_EOL;

$highRaw = array_values(array_filter($raw, fn ($tx) => abs((float) ($tx['amount'] ?? 0)) >= 10000));
$highFinal = array_values(array_filter($final, fn ($tx) => abs((float) ($tx['amount'] ?? 0)) >= 10000));

echo 'raw_high>=10000='.count($highRaw).PHP_EOL;
foreach ($highRaw as $tx) {
    echo 'RAW|'.($tx['date'] ?? '').'|'.($tx['type'] ?? '').'|'.($tx['amount'] ?? '').'|'.mb_substr((string) ($tx['label'] ?? ''), 0, 160).PHP_EOL;
}

echo 'final_high>=10000='.count($highFinal).PHP_EOL;
foreach ($highFinal as $tx) {
    echo 'FINAL|'.($tx['date'] ?? '').'|'.($tx['type'] ?? '').'|'.($tx['amount'] ?? '').'|'.mb_substr((string) ($tx['label'] ?? ''), 0, 160).PHP_EOL;
}

$keywords = ['MILLIET', 'VENTE', 'TERRAIN', 'GORDANO', 'ANGDM'];
foreach ($keywords as $keyword) {
    $rows = array_values(array_filter($final, function ($tx) use ($keyword) {
        $label = mb_strtoupper((string) ($tx['label'] ?? ''));
        return str_contains($label, $keyword);
    }));
    echo 'keyword='.$keyword.' count='.count($rows).PHP_EOL;
    foreach (array_slice($rows, 0, 20) as $tx) {
        echo 'K|'.($tx['date'] ?? '').'|'.($tx['type'] ?? '').'|'.($tx['amount'] ?? '').'|'.mb_substr((string) ($tx['label'] ?? ''), 0, 160).PHP_EOL;
    }
}
