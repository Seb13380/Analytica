<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$s = App\Models\Statement::find(10);
$lines = explode("\n", $s->extracted_text ?? '');

// Check period around April 29, 2023 —  lines 1750-1900  
echo "=== Lines 1750-1900 (april 2023 area) ===\n";
for ($i=1750; $i<=1900 && $i<count($lines); $i++) {
    echo "[$i] ".rtrim($lines[$i])."\n";
}
