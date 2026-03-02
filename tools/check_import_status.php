<?php
require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach ([10, 11] as $id) {
    $s = \App\Models\Statement::find($id);
    if (! $s) {
        echo $id."|missing|0|\n";
        continue;
    }

    echo $id.'|'.$s->import_status.'|'.$s->transactions_imported.'|'.($s->import_error ?? '').PHP_EOL;
}
