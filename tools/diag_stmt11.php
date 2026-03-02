<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// All failed jobs
$all = \DB::table('failed_jobs')->orderByDesc('failed_at')->get();
echo "Total failed jobs: " . $all->count() . "\n";
foreach ($all as $j) {
    echo "  id={$j->id} | {$j->failed_at} | " . substr($j->exception, 0, 150) . "\n";
}

// Jobs table
echo "\nPending jobs: " . \DB::table('jobs')->count() . "\n";
foreach (\DB::table('jobs')->get() as $j) {
    echo "  id={$j->id} | queue={$j->queue} | attempts={$j->attempts} | available_at=" . date('Y-m-d H:i:s', $j->available_at) . "\n";
}

// Statement status
$s11 = \App\Models\Statement::find(11);
echo "\nStatement 11: import_status={$s11->import_status}, transactions_imported={$s11->transactions_imported}, updated_at={$s11->updated_at}\n";
echo "  import_error=" . ($s11->import_error ?? 'null') . "\n";

// Total transactions
echo "Total transactions: " . \DB::table('transactions')->count() . "\n";
