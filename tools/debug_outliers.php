<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Gros montants suspects
echo "=== TOP 20 MONTANTS CREDITS ===\n";
$rows = DB::select("SELECT date, amount, label FROM transactions WHERE amount > 0 ORDER BY amount DESC LIMIT 20");
foreach($rows as $r) echo date('Y-m-d', strtotime($r->date))." | ".number_format($r->amount,2)." | ".substr($r->label,0,80)."\n";

echo "\n=== DATES HORS 2020-2025 ===\n";
$rows = DB::select("SELECT date, amount, label FROM transactions WHERE extract(year from date) NOT BETWEEN 2020 AND 2025 ORDER BY date LIMIT 30");
foreach($rows as $r) echo date('Y-m-d', strtotime($r->date))." | ".number_format($r->amount,2)." | ".substr($r->label,0,80)."\n";
