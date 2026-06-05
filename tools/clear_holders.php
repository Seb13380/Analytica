<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
DB::table('bank_accounts')->whereIn('id', [1, 2])->update(['account_holder' => null]);
echo "Titulaires effacés\n";
