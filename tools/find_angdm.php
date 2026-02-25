<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$s = App\Models\Statement::find(10);
$lines = explode("\n", $s->extracted_text ?? '');

// Find the "29.04" anchor that leads to ANGDM
foreach($lines as $i => $l) {
    if(preg_match('/^29[.\-\/]04\b/', trim($l)) && (stripos($l,'ANGDM') !== false || stripos($l,'VIR SEPA') !== false)) {
        $start = max(0,$i-2);
        $end = min(count($lines)-1, $i+12);
        echo "=== ANCHOR at line $i ===\n";
        for($j=$start; $j<=$end; $j++) {
            echo "[$j] ".rtrim($lines[$j])."\n";
        }
        echo "\n";
    }
}

// Also show all lines that contain "904"
echo "\n=== ALL lines with 904 ===\n";
foreach($lines as $i => $l) {
    if(preg_match('/9\x{00A0}?0\x{00A0}?4/u', $l) || strpos($l,'904') !== false) {
        echo "[$i] ".rtrim($l)."\n";
    }
}
