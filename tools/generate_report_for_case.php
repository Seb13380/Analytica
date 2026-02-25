<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make('Illuminate\\Contracts\\Console\\Kernel');
$kernel->bootstrap();

$caseId = isset($argv[1]) ? (int) $argv[1] : 1;
$format = $argv[2] ?? 'pdf';

$case = App\Models\CaseFile::findOrFail($caseId);

Illuminate\Support\Facades\Auth::loginUsingId((int) $case->user_id);

$request = Illuminate\Http\Request::create('/cases/'.$caseId.'/reports', 'POST', ['format' => $format]);

$controller = app(App\Http\Controllers\ReportController::class);
$response = $controller->generate($request, $case, app(App\Services\EncryptedFileStorage::class));

echo 'status='.$response->getStatusCode().PHP_EOL;
$latest = App\Models\Report::query()->where('case_id', $caseId)->latest('id')->first();
echo 'latest='.(string)($latest?->original_filename ?? '').PHP_EOL;
