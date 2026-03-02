<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check complete meta of first 5 transactions
$samples = DB::select("SELECT id, date, amount, meta FROM transactions ORDER BY date LIMIT 5");
foreach ($samples as $r) {
    echo "id={$r->id} | {$r->date} | {$r->amount}\n";
    $meta = json_decode($r->meta, true) ?? [];
    foreach ($meta as $k => $v) {
        if ($k === 'source_block_lines') { 
            echo "  source_block_lines=[".count($v)." lines]\n";
            continue;
        }
        $val = is_array($v) ? json_encode($v) : (string)$v;
        if (strlen($val) > 80) $val = substr($val, 0, 80).'...';
        echo "  $k=$val\n";
    }
    echo "\n";
}
