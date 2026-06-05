<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Effacer le RIB des comptes qui ont le même RIB que le compte commun (id=1)
// car les sous-comptes (personnel, livret) ont leurs propres numéros différents
$mainRib = \DB::table('bank_accounts')->where('id', 1)->value('rib');
echo "RIB compte commun (id=1): {$mainRib}\n";

$duplicates = \DB::table('bank_accounts')
    ->where('id', '!=', 1)
    ->where('rib', $mainRib)
    ->whereNotNull('rib')
    ->get();

foreach ($duplicates as $acct) {
    \DB::table('bank_accounts')->where('id', $acct->id)->update(['rib' => null]);
    echo "RIB effacé pour compte id={$acct->id} ({$acct->account_holder}) — était un doublon du compte commun\n";
}

echo "\nÉtat final:\n";
foreach (\DB::table('bank_accounts')->get() as $a) {
    echo "id={$a->id} | {$a->bank_name} | {$a->account_holder} | rib=" . ($a->rib ?? 'NULL') . "\n";
}
