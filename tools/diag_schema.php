<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== transactions columns ===\n";
$cols = DB::select("SELECT column_name, data_type FROM information_schema.columns WHERE table_name='transactions' ORDER BY ordinal_position");
foreach ($cols as $c) {
    echo "{$c->column_name} ({$c->data_type})\n";
}

echo "\n=== statements columns ===\n";
$cols2 = DB::select("SELECT column_name, data_type FROM information_schema.columns WHERE table_name='statements' ORDER BY ordinal_position");
foreach ($cols2 as $c) {
    echo "{$c->column_name} ({$c->data_type})\n";
}
