<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$case = \App\Models\CaseFile::first();
echo "Case id={$case->id}\n";
echo "URL: http://localhost:8080/cases/{$case->id}\n";
