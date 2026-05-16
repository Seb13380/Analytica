<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$n1 = DB::update("UPDATE transactions SET origin = NULL WHERE LOWER(TRIM(origin)) = 'titulaire'");
$n2 = DB::update("UPDATE transactions SET destination = NULL WHERE LOWER(TRIM(destination)) = 'titulaire'");

$remaining = DB::selectOne("SELECT COUNT(*) AS cnt FROM transactions WHERE LOWER(origin)='titulaire' OR LOWER(destination)='titulaire'");

echo "origin nettoyés    : $n1\n";
echo "destination nettoyés: $n2\n";
echo "Restants           : {$remaining->cnt}\n";
