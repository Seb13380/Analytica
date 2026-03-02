<?php
/**
 * Debug: show extracted statement period and first 3000 chars of OCR text for each statement.
 */
require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$statements = \App\Models\Statement::whereNotNull('extracted_text')
    ->orderBy('id')
    ->get(['id', 'extracted_text', 'date_start', 'date_end']);

foreach ($statements as $stmt) {
    $text = $stmt->extracted_text ?? '';
    $lines = preg_split('/\R/u', $text) ?? [];
    $lineCount = count($lines);

    // Find first 500 chars
    $head = mb_substr($text, 0, 500);

    // Regex replicas of extractStatementPeriod
    $normalized = mb_strtolower($text);
    $normalized = str_replace(["\n", "\r"], ' ', $normalized);
    $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

    $period = null;
    if (preg_match('/du\s+(\d{2}[\/\-.]\d{2}[\/\-.]\d{2,4})\s+au\s+(\d{2}[\/\-.]\d{2}[\/\-.]\d{2,4})/u', $normalized, $m)) {
        $period = "numeric: {$m[1]} au {$m[2]}";
    } elseif (preg_match('/du\s+(\d{1,2})\s+([\pL\'\x{2019}\-]+)\s+(20\d{2})\s+au\s+(\d{1,2})\s+([\pL\'\x{2019}\-]+)\s+(20\d{2})/u', $normalized, $m)) {
        $period = "named: {$m[1]} {$m[2]} {$m[3]} au {$m[4]} {$m[5]} {$m[6]}";
    }

    // Find first year match in first 1000 chars
    $header = mb_substr($text, 0, 1000);
    $firstYear = null;
    if (preg_match('/\b(20[012]\d)\b/u', $header, $ym)) {
        $firstYear = $ym[1];
    }

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Statement #{$stmt->id}  |  DB period: {$stmt->date_start} → {$stmt->date_end}  |  lines: {$lineCount}\n";
    echo "Extracted period: " . ($period ?? 'NONE') . "\n";
    echo "First 4-digit year in header: " . ($firstYear ?? 'NONE') . "\n";
    echo "--- First 400 chars of OCR text ---\n";
    echo htmlspecialchars_decode($head) . "\n";
    echo "\n";
}
