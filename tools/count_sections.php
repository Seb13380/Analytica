<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$s = App\Models\Statement::find(11);
$text = $s->extracted_text;
$lines = explode("\n", $text);
echo "Total lines: ".count($lines)."\n";
echo "Total chars: ".strlen($text)."\n";
$headers = [];
foreach($lines as $i => $l) {
    if(stripos($l,'RELEVE DE COMPTE') !== false) { $headers[] = $i.': '.$l; }
}
echo "Statement headers: ".count($headers)."\n";
foreach($headers as $h) echo "  $h\n";
echo "\n--- Period lines ---\n";
foreach($lines as $i => $l) {
    if(preg_match('/\bdu\s+\d{1,2}\s+\S+\s+20\d{2}\s+au\s+/ui', $l)) {
        echo "  $i: $l\n";
    }
}
