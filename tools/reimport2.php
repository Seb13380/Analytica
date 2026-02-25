<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$deleted = DB::table('transactions')->delete();
echo "Deleted $deleted transactions\n";
DB::table('statements')->update(['import_status'=>'pending','transactions_imported'=>0,'import_error'=>null,'extracted_text'=>null]);
App\Jobs\ImportStatementJob::dispatch(10);
App\Jobs\ImportStatementJob::dispatch(11);
echo "Jobs dispatched for 10 and 11\n";
