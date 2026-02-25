<?php

// Usage: php tools/dump_statement_text.php <statementId> [maxChars]

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$statementId = (int) ($argv[1] ?? 0);
$maxChars = (int) ($argv[2] ?? 12000);

if ($statementId <= 0) {
    fwrite(STDERR, "Missing statementId\n");
    exit(2);
}

$st = App\Models\Statement::find($statementId);
if (!$st) {
    fwrite(STDERR, "Statement not found: {$statementId}\n");
    exit(3);
}

$text = (string) ($st->extracted_text ?? '');
echo "LEN=" . strlen($text) . "\n";
echo substr($text, 0, $maxChars) . "\n";
