<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$s = App\Models\Statement::find(10);
$lines = explode("\n", $s->extracted_text ?? '');

// Find 904
foreach($lines as $i => $l) {
    if(preg_match('/904[ ,.]?102|904102/', $l)) {
        $start = max(0,$i-4);
        $end = min(count($lines)-1, $i+4);
        echo "--- FOUND AT LINE $i ---\n";
        for($j=$start; $j<=$end; $j++) echo "[$j] ".$lines[$j]."\n";
        echo "\n";
    }
}

// Find TOTAL DES OPERATIONS lines
echo "\n=== TOTAL/SOLDE lines ===\n";
foreach($lines as $i => $l) {
    if(stripos($l,'TOTAL DES OPER') !== false || stripos($l,'SOLDE CRED') !== false || stripos($l,'SOLDE DEB') !== false) {
        echo "[$i] $l\n";
    }
}
