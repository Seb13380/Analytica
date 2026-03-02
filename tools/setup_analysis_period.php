<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$case = \App\Models\CaseFile::first();
echo "Case id={$case->id}, name={$case->full_name}\n";
echo "analysis_period_start=" . ($case->analysis_period_start ?? 'null') . "\n";
echo "analysis_period_end=" . ($case->analysis_period_end ?? 'null') . "\n";

// Set analysis period to Dec 2020 - Oct 2025 (joint account range)
$case->analysis_period_start = '2020-12-01';
$case->analysis_period_end   = '2025-10-31';
$case->save();
echo "\nUpdated analysis period: 2020-12-01 to 2025-10-31\n";

// Also update bank_account id=1 to have account_holder = 'M. ou Mme GIORDANO'
\DB::table('bank_accounts')->where('id', 1)->update([
    'account_holder' => 'M. ou Mme GIORDANO',
]);
echo "Updated bank_account id=1 account_holder to 'M. ou Mme GIORDANO'\n";

echo "\nFinal bank_accounts:\n";
foreach (\DB::table('bank_accounts')->get() as $a) {
    echo "  id={$a->id} | holder=" . ($a->account_holder ?? 'null') . " | bank={$a->bank_name}\n";
}
