<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check raw meta of a few transactions
$samples = DB::select("SELECT id, date, amount, meta FROM transactions LIMIT 3");
foreach ($samples as $r) {
    echo "id={$r->id} | {$r->date} | {$r->amount}\n";
    $meta = json_decode($r->meta, true);
    echo "  account_section=" . ($meta['account_section'] ?? 'KEY_MISSING') . "\n";
    echo "  source_kind=" . ($meta['source_kind'] ?? 'KEY_MISSING') . "\n";
    echo "  confidence=" . ($meta['confidence'] ?? 'KEY_MISSING') . "\n";
    echo "\n";
}
