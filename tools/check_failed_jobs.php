<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$failed = \DB::table('failed_jobs')->orderByDesc('failed_at')->limit(5)->get();
echo "Failed jobs: " . $failed->count() . "\n";
foreach ($failed as $j) {
    echo "  id={$j->id} | {$j->failed_at}\n";
    echo "  exception: " . substr($j->exception, 0, 300) . "\n\n";
}

// Also check jobs queue
$pending = \DB::table('jobs')->count();
echo "Pending jobs in queue: $pending\n";

// Check statement 11
$s = \App\Models\Statement::find(11);
if ($s) {
    echo "Stmt11: status={$s->status}, updated_at={$s->updated_at}\n";
}
