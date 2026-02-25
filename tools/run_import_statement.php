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

$job = new App\Jobs\ImportStatementJob($statementId);
$job->handle(
    app(App\Services\EncryptedFileStorage::class),
    app(App\Services\StatementImportService::class),
    app(App\Services\AnalysisEngine::class),
);

echo "Imported statement {$statementId}\n";
