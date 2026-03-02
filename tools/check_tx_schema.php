<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$cols = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name='transactions' ORDER BY ordinal_position");
foreach ($cols as $c) echo $c->column_name . "\n";
