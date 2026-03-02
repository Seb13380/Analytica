<?php
/**
 * Clean slate reimport: delete all transactions, reset statements, dispatch jobs.
 */
require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$deleted = DB::table('transactions')->delete();
echo "Deleted $deleted transactions\n";

DB::table('statements')
    ->whereIn('id', [10, 11])
    ->update([
        'import_status'         => 'pending',
        'transactions_imported' => 0,
        'import_error'          => null,
        'extracted_text'        => null,   // force full re-OCR
    ]);
echo "Reset statements 10 and 11 to pending\n";

\App\Jobs\ImportStatementJob::dispatch(10);
\App\Jobs\ImportStatementJob::dispatch(11);
echo "Jobs dispatched for statements 10 and 11\n";
