<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

$case = App\Models\CaseFile::with('bankAccounts')->find(1);
$ids = $case?->bankAccounts?->pluck('id')->all() ?? [];

echo 'accounts='.count($ids).PHP_EOL;
$q = App\Models\Transaction::query()->whereIn('bank_account_id', $ids);
echo 'tx='.$q->count().PHP_EOL;
echo 'high>=20000='.(clone $q)->whereRaw('abs(amount)>=20000')->count().PHP_EOL;

$dupes = (clone $q)
    ->selectRaw('bank_account_id,date,type,amount,count(*) c')
    ->groupBy('bank_account_id','date','type','amount')
    ->havingRaw('count(*) > 1')
    ->orderByDesc('c')
    ->limit(40)
    ->get();

echo 'dupe_groups='.$dupes->count().PHP_EOL;
foreach ($dupes as $d) {
    echo $d->bank_account_id.'|'.$d->date.'|'.$d->type.'|'.$d->amount.'|x'.$d->c.PHP_EOL;
}

$t180 = (clone $q)
    ->whereRaw('abs(amount) between 179999.99 and 180000.01')
    ->orderBy('date')
    ->get(['id','date','type','amount','label']);

echo 'tx180='.$t180->count().PHP_EOL;
foreach ($t180 as $t) {
    echo $t->id.'|'.$t->date.'|'.$t->type.'|'.$t->amount.'|'.mb_substr($t->label,0,100).PHP_EOL;
}
