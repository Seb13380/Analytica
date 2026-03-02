<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Use model to get decrypted extracted_text
$stmt = App\Models\Statement::find(10);
$text = (string)($stmt->extracted_text ?? '');

echo "=== extracted_text length (decrypted): ".strlen($text)." ===\n";
echo "=== First 200 chars ===\n";
echo substr($text, 0, 200)."\n\n";

// Check if isLikelyBnp
$service = app(\App\Services\StatementImportService::class);
$isBnp = $service->isLikelyBnp($text);
echo "=== isLikelyBnp: ".($isBnp ? 'TRUE' : 'FALSE')." ===\n\n";

// Look for section markers
$upper = mb_strtoupper($text);
echo "Has RELEVE DE COMPTE: ".(str_contains($upper, 'RELEVE DE COMPTE') ? 'YES' : 'NO')."\n";
echo "Has BNP: ".(str_contains($upper, 'BNP') ? 'YES' : 'NO')."\n";
echo "Has M OU MME: ".(str_contains($upper, 'M OU MME') ? 'YES' : 'NO')."\n";
echo "Has RELEVE LIVRET: ".(str_contains($upper, 'RELEVE LIVRET') ? 'YES' : 'NO')."\n\n";

// Parse first 50k chars only to check detection
$shortText = mb_substr($text, 0, 50000);
$txs = $service->parseTransactionsFromText($shortText, 2022);
echo "=== Parsed ".count($txs)." transactions ===\n";
if ($txs) {
    $sample = $txs[0];
    echo "First tx date: {$sample['date']}, amount: {$sample['amount']}\n";
    $section = $sample['meta']['account_section'] ?? 'KEY_MISSING';
    $sourceKind = $sample['meta']['source_kind'] ?? 'KEY_MISSING';
    $confidence = $sample['meta']['confidence'] ?? 'KEY_MISSING';
    echo "account_section={$section}, source_kind={$sourceKind}, confidence={$confidence}\n";
}

