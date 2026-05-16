<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rows = DB::select('SELECT id, date, amount, type, kind, LEFT(label,120) as lb FROM transactions WHERE ABS(amount) BETWEEN 179000 AND 182000 ORDER BY date');
if (empty($rows)) {
    echo "No transactions found between 179 000 and 182 000.\n";
    // Search wider
    $rows2 = DB::select("SELECT id, date, amount, type, kind, LEFT(label,120) as lb FROM transactions WHERE ABS(amount) BETWEEN 178000 AND 183000 ORDER BY date");
    echo "Wider range (178k-183k): " . count($rows2) . " rows\n";
    foreach ($rows2 as $r) echo $r->id.'|'.$r->date.'|'.$r->amount.'|'.$r->type.'|'.$r->kind.'|'.$r->lb."\n";
} else {
    foreach ($rows as $r) echo $r->id.'|'.$r->date.'|'.$r->amount.'|'.$r->type.'|'.$r->kind.'|'.$r->lb."\n";
}
// Also check if the 180017.98 value exists anywhere
$check = DB::select("SELECT id, date, amount, type, kind, LEFT(label,120) as lb FROM transactions WHERE amount BETWEEN 180000 AND 180100 OR amount BETWEEN -180100 AND -180000 ORDER BY date");
echo "\n--- Transactions between 180000 and 180100 ---\n";
foreach ($check as $r) echo $r->id.'|'.$r->date.'|'.$r->amount.'|'.$r->type.'|'.$r->kind.'|'.$r->lb."\n";
if (empty($check)) echo "(none)\n";
