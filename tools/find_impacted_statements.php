<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\Models\Statement;

$targets = [
    'du 22 décembre 2022 au 22 janvier 2023',
    'du 22 septembre 2023 au 22 octobre 2023',
    'du 22 août 2023 au 22 septembre 2023',
];

$rows = Statement::query()
    ->whereNotNull('extracted_text')
    ->where(function ($q) use ($targets) {
        foreach ($targets as $needle) {
            $q->orWhere('extracted_text', 'ilike', '%'.$needle.'%');
        }
    })
    ->orderBy('id')
    ->get(['id','bank_account_id','original_filename','import_status','transactions_imported','created_at']);

echo "Impacted statements count: ".count($rows)."\n";
foreach ($rows as $s) {
    echo sprintf("#%d | account:%d | status:%s | tx:%s | %s\n", $s->id, $s->bank_account_id, (string)$s->import_status, (string)$s->transactions_imported, (string)($s->original_filename ?? 'n/a'));
}
