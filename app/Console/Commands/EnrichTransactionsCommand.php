<?php

namespace App\Console\Commands;

use App\Models\BankAccount;
use App\Models\CaseFile;
use App\Models\Transaction;
use App\Services\LlmEnricher;
use Illuminate\Console\Command;

/**
 * Enrichit les champs origin/destination/motif/kind des transactions via LLM.
 *
 * Usage:
 *   php artisan analytica:enrich-transactions 5          # dossier ID=5
 *   php artisan analytica:enrich-transactions --all      # tous les dossiers
 *   php artisan analytica:enrich-transactions 5 --force  # re-corriger même les champs déjà remplis
 *   php artisan analytica:enrich-transactions 5 --dry-run # afficher sans modifier
 */
class EnrichTransactionsCommand extends Command
{
    protected $signature = 'analytica:enrich-transactions
        {case? : ID du dossier à traiter}
        {--all : Traiter tous les dossiers}
        {--force : Ré-enrichir même les transactions déjà enrichies}
        {--dry-run : Simuler sans modifier la base}
        {--limit=0 : Limiter le nombre de transactions traitées (0 = toutes)}';

    protected $description = 'Enrichit origin/destination/motif/kind des transactions via LLM (GPT-4.1-mini par défaut)';

    public function handle(LlmEnricher $enricher): int
    {
        $caseId  = $this->argument('case');
        $all     = $this->option('all');
        $force   = (bool) $this->option('force');
        $dryRun  = (bool) $this->option('dry-run');
        $limit   = (int) $this->option('limit');

        if (!config('analytica.ai.enabled')) {
            $this->error('ANALYTICA_AI_ENABLED est false. Activez-le dans .env avant de lancer cette commande.');
            return self::FAILURE;
        }

        if (!env('OPENAI_API_KEY')) {
            $this->error('OPENAI_API_KEY manquante dans .env.');
            return self::FAILURE;
        }

        if (!$caseId && !$all) {
            $this->error('Précisez un ID de dossier ou utilisez --all.');
            $this->line('Exemples:');
            $this->line('  php artisan analytica:enrich-transactions 5');
            $this->line('  php artisan analytica:enrich-transactions --all');
            return self::FAILURE;
        }

        // Construire la requête
        $query = Transaction::query();

        if ($caseId) {
            /** @var CaseFile $case */
            $case = CaseFile::find($caseId);
            if (!$case) {
                $this->error("Dossier ID={$caseId} introuvable.");
                return self::FAILURE;
            }
            $accountIds = $case->bankAccounts()->pluck('id');
            $query->whereIn('bank_account_id', $accountIds);
            $this->info("Dossier: {$case->title} (ID={$case->id}) — " . $accountIds->count() . " compte(s).");
        }

        if (!$force) {
            // Ne traiter que les transactions sans enrichissement complet
            $query->where(function ($q) {
                $q->whereNull('origin')
                  ->orWhereNull('destination')
                  ->orWhereNull('motif')
                  ->orWhere('kind', 'other')
                  ->orWhere('kind', null);
            });
        }

        $query->orderBy('date');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $total = $query->count();

        if ($total === 0) {
            $this->info('Aucune transaction à enrichir.');
            return self::SUCCESS;
        }

        $this->info("Transactions à traiter : {$total}");

        if ($dryRun) {
            $this->warn('[DRY-RUN] Aucune modification ne sera effectuée.');
            // Afficher un aperçu
            $query->limit(10)->get()->each(function ($tx) {
                $this->line(sprintf(
                    '  id=%-6d | %s | %s%s € | origin=%s | dest=%s | motif=%s',
                    $tx->id, $tx->date->format('Y-m-d'),
                    $tx->amount >= 0 ? '+' : '',
                    number_format((float) $tx->amount, 2, ',', ' '),
                    $tx->origin ?? '(vide)',
                    $tx->destination ?? '(vide)',
                    $tx->motif ?? '(vide)',
                ));
            });
            if ($total > 10) {
                $this->line("  ... et " . ($total - 10) . " autres.");
            }
            return self::SUCCESS;
        }

        $bar     = $this->output->createProgressBar($total);
        $updated = 0;

        $query->chunk(25, function ($chunk) use ($enricher, $force, $bar, &$updated) {
            if ($force) {
                $count = $enricher->forceEnrichTransactions($chunk);
            } else {
                $count = $enricher->enrichTransactions($chunk);
            }
            $updated += $count;
            $bar->advance($chunk->count());
        });

        $bar->finish();
        $this->newLine(2);

        $this->info("Enrichissement terminé : {$updated} / {$total} transactions mises à jour.");

        return self::SUCCESS;
    }
}
