<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$stmt = \App\Models\Statement::find(1);
$upper = mb_strtoupper($stmt->extracted_text ?? '');
$lines = explode("\n", $upper);
$found = false;
foreach ($lines as $i => $l) {
    $l = trim($l);
    if (preg_match('/\bM\b.{0,8}\b(?:OU|ET)\b.{0,8}\bMME\b/u', $l) && strlen($l) < 80) {
        echo "L{$i}: {$l}\n";
        if (!$found) { $found = true; }
        if ($i > 500) break;
    }
}
echo "\nPremière ligne contenant GIORDANO dans les 500 premières :\n";
foreach (array_slice($lines, 0, 500) as $i => $l) {
    if (str_contains($l, 'GIORDANO')) {
        echo "L{$i}: " . trim($l) . "\n";
        break;
    }
}
