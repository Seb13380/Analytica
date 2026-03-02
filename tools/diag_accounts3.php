<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== bank_accounts columns ===\n";
$cols = DB::select("SELECT column_name, data_type FROM information_schema.columns WHERE table_name='bank_accounts' ORDER BY ordinal_position");
foreach ($cols as $c) {
    echo "{$c->column_name} ({$c->data_type})\n";
}

echo "\n=== bank_accounts data ===\n";
$accts = DB::table('bank_accounts')->get();
foreach ($accts as $a) {
    echo json_encode($a)."\n";
}
