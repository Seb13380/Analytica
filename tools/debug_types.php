<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Show duplicates
echo "=== DOUBLONS (même date + même montant absolu) ===\n";
$rows = DB::select("
    SELECT date, ABS(amount) as abs_amount, type, COUNT(*) as n, 
           array_agg(LEFT(label,60)) as labels
    FROM transactions
    GROUP BY date, ABS(amount), type
    HAVING COUNT(*) > 1
    ORDER BY ABS(amount) DESC
    LIMIT 20
");
foreach($rows as $r) {
    echo date('Y-m-d',strtotime($r->date))." | ".number_format($r->abs_amount,2)." | ".$r->type." | x".$r->n."\n";
    foreach(json_decode($r->labels) as $l) echo "  - $l\n";
    echo "\n";
}

echo "\n=== VIREMENTS EMIS: type actuel ===\n";
$rows = DB::select("SELECT date, amount, type, LEFT(label,80) as label FROM transactions WHERE (label ILIKE '%VIREMENT SEPA EMIS%' OR label ILIKE '%VIR%EMIS%' OR label ILIKE '%EMSS%') ORDER BY ABS(amount) DESC LIMIT 15");
foreach($rows as $r) echo date('Y-m-d',strtotime($r->date))." | ".number_format($r->amount,2)." | ".$r->type." | ".$r->label."\n";
