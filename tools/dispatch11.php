<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
App\Jobs\ImportStatementJob::dispatch(11);
echo "Job dispatched for statement 11\n";
