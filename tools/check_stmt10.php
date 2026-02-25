<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$s = App\Models\Statement::find(10);
$text = $s->extracted_text;
$lines = explode("\n", $text);
echo "Stmt 10 - Total lines: ".count($lines).", chars: ".strlen($text)."\n";
echo "--- Period lines ---\n";
foreach($lines as $i => $l) {
    if(preg_match('/\bdu\s+\d{1,2}\s+\S+\s+20\d{2}\s+au\s+/ui', $l)) {
        echo "  $i: ".trim($l)."\n";
    }
}
echo "\n--- Statements in DB ---\n";
$stmts = App\Models\Statement::all();
foreach($stmts as $st) {
    echo "ID:".$st->id." | ".$st->file_name." | txs:".$st->transactions_imported."\n";
}
