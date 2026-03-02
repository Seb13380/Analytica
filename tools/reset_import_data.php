<?php

require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$deleted = DB::table('transactions')->delete();
DB::table('statements')->update([
    'import_status' => 'pending',
    'transactions_imported' => 0,
    'import_error' => null,
]);

echo "Deleted transactions: {$deleted}\n";
echo "Statements reset: ok\n";
