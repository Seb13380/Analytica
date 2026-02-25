<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$stmts = App\Models\Statement::all();
foreach($stmts as $s) {
    echo "ID:".$s->id." | ".$s->import_status." | tx:".$s->transactions_imported." | ".substr($s->import_error??'',0,80)."\n";
}
