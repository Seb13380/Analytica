<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Merge account id=4 ("M. ou Mme GIORDANO (personnel)") into id=2 ("Compte personnel")
$from = 4;
$to   = 2;

$cnt = \DB::table('transactions')->where('bank_account_id', $from)->count();
echo "Transactions in account $from: $cnt\n";

// First find which transactions in account 4 are already in account 2 (duplicates)
$conflicts = \DB::select("
    SELECT t4.id
    FROM transactions t4
    WHERE t4.bank_account_id = :from
    AND EXISTS (
        SELECT 1 FROM transactions t2
        WHERE t2.bank_account_id = :to
        AND t2.date = t4.date
        AND t2.amount = t4.amount
        AND t2.type = t4.type
        AND t2.normalized_label = t4.normalized_label
    )
", ['from' => $from, 'to' => $to]);

$conflictIds = array_column($conflicts, 'id');
echo "Conflicts (already in account $to): " . count($conflictIds) . "\n";

if ($conflictIds) {
    \DB::table('transactions')->whereIn('id', $conflictIds)->delete();
    echo "Deleted " . count($conflictIds) . " conflicting duplicates from account $from.\n";
}

// Now move the rest
$moved = \DB::table('transactions')->where('bank_account_id', $from)->update(['bank_account_id' => $to]);
echo "Moved $moved transactions from account $from to account $to.\n";

\DB::table('bank_accounts')->where('id', $from)->delete();
echo "Deleted bank_account id=$from.\n";

echo "\nFinal bank_accounts:\n";
foreach (\DB::table('bank_accounts')->orderBy('id')->get() as $a) {
    $cnt = \DB::table('transactions')->where('bank_account_id', $a->id)->count();
    echo "  id={$a->id} | holder=" . ($a->account_holder ?? 'null') . " → $cnt tx\n";
}
echo "\nTotal transactions: " . \DB::table('transactions')->count() . "\n";
