<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Find the OCR text block that generated the 904k
$s = App\Models\Statement::find(10);
$text = $s->extracted_text ?? '';
$lines = explode("\n", $text);

echo "=== Lines around 904102 ===\n";
foreach($lines as $i => $l) {
    if(str_contains($l, '904') || str_contains($l, 'ANGDM') || str_contains($l, 'DUNE')) {
        $start = max(0, $i-3);
        $end = min(count($lines)-1, $i+5);
        echo "--- line $i ---\n";
        for($j=$start; $j<=$end; $j++) echo "  [$j] ".$lines[$j]."\n";
        echo "\n";
    }
}

echo "\n=== Lines around 2028 dates ===\n";
foreach($lines as $i => $l) {
    if(preg_match('/190428|080628|0428|0628/', $l)) {
        $start = max(0, $i-2);
        $end = min(count($lines)-1, $i+3);
        echo "--- line $i ---\n";
        for($j=$start; $j<=$end; $j++) echo "  [$j] ".$lines[$j]."\n";
        echo "\n";
    }
}
