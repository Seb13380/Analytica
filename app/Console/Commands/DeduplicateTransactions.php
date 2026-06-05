<?php

namespace App\Console\Commands;

use App\Models\BankAccount;
use App\Models\CaseFile;
use App\Services\DeduplicationService;
use Illuminate\Console\Command;

class DeduplicateTransactions extends Command
{
    protected $signature = 'analytica:dedup
                            {--case= : ID du dossier à dédupliquer}
                            {--account= : ID du compte à dédupliquer}
                            {--all : Tous les dossiers}
                            {--dry-run : Simulation sans suppression}';

    protected $description = 'Supprime les doublons OCR dans les transactions (double passe : exact + fuzzy)';

    public function handle(DeduplicationService $dedup): int
    {
        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->warn('Mode DRY-RUN : aucune suppression ne sera effectuée.');
        }

        $totalDeleted = 0;

        if ($accountId = $this->option('account')) {
            $account = BankAccount::findOrFail((int) $accountId);
            $stats = $dedup->deduplicateAccount($account, $dryRun);
            $this->printStats("Compte #{$account->id}", $stats, $dryRun);
            $totalDeleted += $stats['deleted'];

        } elseif ($caseId = $this->option('case')) {
            $results = $dedup->deduplicateCase((int) $caseId, $dryRun);
            foreach ($results as $accId => $stats) {
                $this->printStats("Compte #{$accId}", $stats, $dryRun);
                $totalDeleted += $stats['deleted'];
            }

        } elseif ($this->option('all')) {
            foreach (CaseFile::all() as $case) {
                $results = $dedup->deduplicateCase($case->getKey(), $dryRun);
                foreach ($results as $accId => $stats) {
                    $this->printStats("Dossier #{$case->getKey()} Compte #{$accId}", $stats, $dryRun);
                    $totalDeleted += $stats['deleted'];
                }
            }
        } else {
            $this->error('Précisez --case=ID, --account=ID ou --all');
            return 1;
        }

        $this->info($dryRun
            ? "Simulation terminée. {$totalDeleted} doublon(s) auraient été supprimés."
            : "Déduplication terminée. {$totalDeleted} doublon(s) supprimé(s)."
        );
        return 0;
    }

    private function printStats(string $label, array $stats, bool $dryRun): void
    {
        if ($stats['duplicates_found'] === 0) {
            $this->line("  {$label}: aucun doublon.");
            return;
        }
        $action = $dryRun ? 'à supprimer' : 'supprimés';
        $this->warn("  {$label}: {$stats['duplicates_found']} doublon(s) {$action} sur {$stats['examined']} transactions.");
        foreach ($stats['details'] as $d) {
            $this->line("    [{$d['date']} {$d['amount']}€] garde #{$d['kept']} — supprime #{$d['deleted']} ({$d['reason']})");
            $this->line("      A: {$d['label_a']}");
            $this->line("      B: {$d['label_b']}");
        }
    }
}
