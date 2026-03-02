<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$s = \App\Models\Statement::find(11);
$text = $s->extracted_text; // auto-decrypted
echo "extracted_text length: " . strlen($text ?? '') . "\n";
echo "First 200 chars:\n" . substr($text ?? '', 0, 200) . "\n";

// Reset stmt11 status to pending
\DB::table('statements')->where('id', 11)->update([
    'import_status' => 'pending',
    'import_error' => null,
]);
echo "\nStmt11 reset to pending.\n";
