<?php

namespace App\Jobs;

use App\Models\Statement;
use App\Models\Transaction;
use App\Services\AnalysisEngine;
use App\Services\EncryptedFileStorage;
use App\Services\Normalization;
use App\Services\StatementImportService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Smalot\PdfParser\Parser;

class ImportStatementJob implements ShouldQueue
{
    use Queueable;

    /** Maximum execution time in seconds (15 min for large OCR PDFs). */
    public int $timeout = 900;

    /** Number of allowed attempts before marking as failed. */
    public int $tries = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $statementId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(
        EncryptedFileStorage $storage,
        StatementImportService $importer,
        AnalysisEngine $engine,
        \App\Services\LlmEnricher $enricher,
    ): void {
        $statement = Statement::query()
            ->with(['bankAccount.caseFile.bankAccounts'])
            ->findOrFail($this->statementId);

        $bankAccount = $statement->bankAccount;
        $case = $bankAccount->caseFile;

        $statement->forceFill([
            'import_status' => 'processing',
            'import_error' => null,
        ])->save();

        $cachedExtractedText = is_string($statement->extracted_text ?? null)
            ? (string) $statement->extracted_text
            : '';

        try {
            $bytes = $storage->getDecryptedBytes($statement->file_path, $statement->encryption_meta ?? []);
            $sourceText = null;

            $mime = (string) ($statement->mime_type ?? '');
            $isPdf = str_contains($mime, 'pdf')
                || str_ends_with(strtolower((string) ($statement->original_filename ?? '')), '.pdf')
                || str_starts_with($bytes, '%PDF-');

            if ($isPdf) {
                $text = $this->extractPdfText($bytes);
                $sourceText = $text;

                $defaultYear = $this->guessDefaultYear($statement->original_filename, $text);

                // Parse from extracted text first.
                $transactions = $importer->parseTransactionsFromText($text, $defaultYear);

                $ocrUsed = false;
                $ocrError = null;

                $mayAttemptOcr = $this->shouldAttemptOcr($text);

                // If text extraction is poor (scanned PDF) OR parsing yields no transactions, try OCR.
                if ($mayAttemptOcr && (mb_strlen($text) < 80 || $transactions === [] || $this->isLikelyIncompletePdfParse($text, $transactions))) {
                    try {
                        $pageCount = $this->getPdfPageCount($bytes);
                        $dpi = (int) env('ANALYTICA_OCR_DPI', 250);
                        $psm = (int) env('ANALYTICA_OCR_PSM', 6);

                        if ($pageCount > 15) {
                            // Large multi-page PDF: paginate OCR over ALL pages in batches.
                            $ocrText = $this->ocrPdfFull($bytes, $pageCount, $dpi, $psm, true);
                            if ($ocrText !== '') {
                                $updatedYear = $this->guessDefaultYear($statement->original_filename, $ocrText);
                                $ocrTx = $importer->parseTransactionsFromText($ocrText, $updatedYear ?? $defaultYear);
                                if (count($ocrTx) > count($transactions)) {
                                    $text = $ocrText;
                                    $sourceText = $ocrText;
                                    $transactions = $ocrTx;
                                    $ocrUsed = true;
                                }
                            }
                        } else {
                            // Small PDF: try multiple quality profiles, keep best result.
                            $firstPage = $this->guessOcrFirstPage($text);
                            $profiles = $this->buildOcrProfiles($firstPage);

                            $bestText = '';
                            $bestTransactions = [];
                            $bestScore = -1;

                            foreach ($profiles as $profile) {
                                $ocrText = $this->ocrPdf(
                                    $bytes,
                                    $profile['first_page'],
                                    $profile['max_pages'],
                                    $profile['dpi'],
                                    $profile['psm'],
                                    $profile['preprocess'],
                                );

                                if ($ocrText === '') {
                                    continue;
                                }

                                $ocrTx = $importer->parseTransactionsFromText($ocrText, $defaultYear);
                                $score = $this->scoreOcrCandidate($ocrText, $ocrTx);

                                if ($score > $bestScore) {
                                    $bestScore = $score;
                                    $bestText = $ocrText;
                                    $bestTransactions = $ocrTx;
                                }
                            }

                            if (count($bestTransactions) > count($transactions)) {
                                $text = $bestText;
                                $sourceText = $bestText;
                                $transactions = $bestTransactions;
                                $ocrUsed = true;
                            }
                        }
                    } catch (ConnectionException $e) {
                        // OCR timeouts / connection issues shouldn't fail the whole import.
                        $ocrError = $e->getMessage();
                    } catch (\Throwable $e) {
                        $ocrError = $e->getMessage();
                    }
                }

                // Fallback resilience: si l'OCR live a échoué (0 transactions) OU a retourné
                // moins de X% des transactions issues du texte OCR déjà stocké en base,
                // réutiliser ce texte stocké plutôt que de perdre des données.
                if (trim($cachedExtractedText) !== '') {
                    $cachedYear    = $this->guessDefaultYear($statement->original_filename, $cachedExtractedText) ?? $defaultYear;
                    $cachedTx      = $importer->parseTransactionsFromText($cachedExtractedText, $cachedYear);
                    $liveCount     = count($transactions);
                    $cachedCount   = count($cachedTx);
                    $cacheFallbackRatio = (float) config('analytica.import.ocr_cache_fallback_ratio', 0.50);

                    $useCachedFallback = $cachedTx !== []
                        && ($liveCount === 0
                            || ($cachedCount > 0 && $liveCount < (int) ceil($cachedCount * $cacheFallbackRatio)));

                    if ($useCachedFallback) {
                        if ($liveCount > 0) {
                            // OCR gave some results but not enough — log degradation.
                            \Illuminate\Support\Facades\Log::warning(
                                "ImportStatementJob [stmt={$this->statementId}]: OCR live ({$liveCount} tx) < {$cacheFallbackRatio}×cache ({$cachedCount} tx). Basculement sur texte OCR stocké."
                            );
                        }
                        $text        = $cachedExtractedText;
                        $sourceText  = $cachedExtractedText;
                        $transactions = $cachedTx;
                        $ocrUsed     = true;
                    }
                }

                $statement->forceFill([
                    'extracted_text' => $text,
                    'ocr_used' => $ocrUsed,
                ])->save();

                // ── Extract RIB/IBAN from statement header ─────────────────────────
                $ribExtracted = $this->extractRibFromText($text);
                if ($ribExtracted !== null && empty($bankAccount->rib)) {
                    $bankAccount->forceFill(['rib' => $ribExtracted])->save();
                }

                // ── Déduplication par RIB : si un autre compte du dossier a ce même RIB,
                //    rediriger ce relevé (et ses futures transactions) vers lui ────────
                if ($ribExtracted !== null) {
                    $ribNorm = preg_replace('/\s+/', '', $ribExtracted);
                    $matchingAccount = \App\Models\BankAccount::where('case_id', $case->getKey())
                        ->where('id', '!=', $bankAccount->getKey())
                        ->get()
                        ->first(function ($acc) use ($ribNorm) {
                            return preg_replace('/\s+/', '', (string)($acc->rib ?? '')) === $ribNorm;
                        });

                    if ($matchingAccount !== null) {
                        // Ce relevé appartient à un compte déjà existant — on y rattache tout
                        $statement->forceFill(['bank_account_id' => $matchingAccount->getKey()])->save();
                        $bankAccount = $matchingAccount;
                        \Illuminate\Support\Facades\Log::info(
                            "ImportStatementJob [stmt={$this->statementId}]: redirection vers compte #{$matchingAccount->getKey()} (même RIB {$ribExtracted})"
                        );
                    }
                }

                // ── Extract account holder name from statement header ─────────────
                $genericHolders = ['compte personnel', 'livret dev / épargne', 'livret dev / epargne', 'm. / mme', 'm. ou mme', ''];
                if (empty($bankAccount->account_holder) || in_array(mb_strtolower(trim($bankAccount->account_holder)), $genericHolders)) {
                    $holderExtracted = $this->extractHolderFromText($text);
                    if ($holderExtracted !== null) {
                        $bankAccount->forceFill(['account_holder' => $holderExtracted])->save();
                    }
                }

                // ── Detect account type (joint/savings/personal) + intitulé complet ─
                $upperText = mb_strtoupper($text);
                if (empty($bankAccount->account_type)) {
                    if (preg_match('/\bM\.?\s+(?:OU|ET)\s+MME\.?\b/u', $upperText)) {
                        $bankAccount->forceFill(['account_type' => 'joint'])->save();
                    } elseif (preg_match('/\bLIVRET\b|\bEPARGNE\b|\bLDD\b|\bLDDS\b|\bLEP\b/u', $upperText)) {
                        $bankAccount->forceFill(['account_type' => 'savings'])->save();
                    } else {
                        $bankAccount->forceFill(['account_type' => 'personal'])->save();
                    }
                }
                if (empty($bankAccount->account_label)) {
                    $labelExtracted = $this->extractAccountLabelFromText($text);
                    if ($labelExtracted !== null) {
                        $bankAccount->forceFill(['account_label' => $labelExtracted])->save();
                    }
                }

                if ($transactions === []) {
                    $statement->forceFill([
                        'import_status' => 'completed',
                        'transactions_imported' => 0,
                        'import_error' => $this->buildNoTransactionsMessage($text, $mayAttemptOcr, $ocrError),
                    ])->save();
                    return;
                }

                $strict = (bool) config('analytica.import.strict', true);
                $minConfidence = (int) config('analytica.import.min_confidence', 45);
                $transactions = $importer->finalizeTransactions($transactions, $text, $strict ? $minConfidence : null);

                $highValueThreshold = (float) config('analytica.import.high_value_threshold', 20000);
                $textHighValues = $importer->detectHighValueAmountsFromText($text, $highValueThreshold);
                $parsedHighValues = collect($transactions)
                    ->map(fn ($tx) => round(abs((float) ($tx['amount'] ?? 0)), 2))
                    ->filter(fn ($value) => $value >= $highValueThreshold)
                    ->unique()
                    ->sortDesc()
                    ->values()
                    ->all();

                if ($textHighValues !== [] && $parsedHighValues === []) {
                    $statement->forceFill([
                        'import_status' => 'failed',
                        'transactions_imported' => 0,
                        'import_error' => sprintf(
                            'Échec contrôle qualité import: montants élevés visibles dans le relevé OCR (%s) mais absents des transactions extraites. Réimport recommandé avec diagnostic.',
                            implode(', ', array_map(fn ($v) => number_format((float) $v, 2, ',', ' '), array_slice($textHighValues, 0, 5)))
                        ),
                    ])->save();

                    return;
                }

                if ($textHighValues !== [] && $parsedHighValues !== []) {
                    $maxText = (float) max($textHighValues);
                    $maxParsed = (float) max($parsedHighValues);

                    // Keep a strict gate only for MASSIVE mismatches.
                    // OCR can overestimate some cheque amounts (e.g. 30 000 read as 80 000),
                    // so medium mismatches should not hard-fail the whole import.
                    $massiveMismatch = $maxText >= 100000
                        && $maxParsed < ($maxText * 0.35)
                        && ($maxText - $maxParsed) >= 50000;

                    if ($massiveMismatch) {
                        $statement->forceFill([
                            'import_status' => 'failed',
                            'transactions_imported' => 0,
                            'import_error' => sprintf(
                                'Échec contrôle qualité import: montant élevé OCR détecté (max %s) mais extraction incohérente (max %s).',
                                number_format($maxText, 2, ',', ' '),
                                number_format($maxParsed, 2, ',', ' ')
                            ),
                        ])->save();

                        return;
                    }
                }
            } else {
                $transactions = $importer->parseTransactions($bytes);
                $transactions = $importer->verifyBalanceCoherence($transactions);
                $strict = (bool) config('analytica.import.strict', true);
                $minConfidence = (int) config('analytica.import.min_confidence', 45);
                $transactions = $importer->finalizeTransactions($transactions, null, $strict ? $minConfidence : null);
            }

            if ($transactions === []) {
                $statement->forceFill([
                    'import_status' => 'completed',
                    'transactions_imported' => 0,
                ])->save();
                return;
            }

            $inserted = 0;
            $insertErrors = 0;
            $firstInsertError = null;
            $deletedExisting = 0;

            $cleanupRange           = $this->resolveCleanupRange($transactions);
            $allowDbDedupHeuristics = (bool) config('analytica.import.allow_db_dedup_heuristics', false);

            if ($cleanupRange !== null) {
                // ── Sécurité cleanup: compter AVANT de supprimer ─────────────────────────
                // On ne supprime les transactions existantes que si les nouvelles en couvrent
                // au moins cleanup_min_coverage_ratio (80% par défaut).
                // Cela protège deux scénarios critiques :
                //   (A) retry attempt 2 : le premier essai a inséré N tx, l'OCR du second
                //       essai est partiel → on ne doit pas effacer plus qu'on peut remplacer.
                //   (B) re-import accidentel avec un OCR dégradé (timeout partiel).
                $existingCount   = Transaction::query()
                    ->where('bank_account_id', $bankAccount->getKey())
                    ->whereBetween('date', [$cleanupRange['start'], $cleanupRange['end']])
                    ->count();

                $newCount        = count($transactions);
                $minCoverage     = (float) config('analytica.import.cleanup_min_coverage_ratio', 0.80);
                $coverageOk      = $existingCount === 0
                    || $newCount >= (int) ceil($existingCount * $minCoverage);

                if ($coverageOk) {
                    $deletedExisting = Transaction::query()
                        ->where('bank_account_id', $bankAccount->getKey())
                        ->whereBetween('date', [$cleanupRange['start'], $cleanupRange['end']])
                        ->delete();
                } else {
                    \Illuminate\Support\Facades\Log::warning(
                        "ImportStatementJob [stmt={$this->statementId}]: cleanup annulé. "
                        ."Existant={$existingCount}, nouveaux={$newCount} (< {$minCoverage}×). "
                        ."Conservation des données existantes pour éviter la perte nette."
                    );
                    // Le flag unique SQL transactions_dedupe gérera l'idempotence lors des inserts.
                }
            }

            // ── Build account section map for multi-account PDFs ────────────────────
            // BNP PDFs can embed multiple account sections (joint account / personal
            // account / livret). Each section is tagged in meta.account_section.
            // We find or create a separate BankAccount record per section so that
            // transactions for each account are stored and filtered independently.
            /** @var array<string,int> $sectionAccountMap */
            $sectionAccountMap = ['joint' => $bankAccount->getKey()];

            $distinctSections = collect($transactions)
                ->map(fn ($t) => (string) ((is_array($t['meta'] ?? null) ? ($t['meta']['account_section'] ?? null) : null) ?? 'joint'))
                ->filter(fn ($s) => $s !== 'joint')
                ->unique()
                ->values();

            foreach ($distinctSections as $section) {
                $holderLabel = match ($section) {
                    'savings'  => 'Livret DEV / Épargne',
                    'personal' => 'Compte personnel',
                    default    => ucfirst((string) $section),
                };
                $sectionAcct = \App\Models\BankAccount::query()
                    ->where('case_id', $case->getKey())
                    ->where('account_holder', $holderLabel)
                    ->first()
                    ?? \App\Models\BankAccount::create([
                        'case_id'        => $case->getKey(),
                        'bank_name'      => $bankAccount->bank_name,
                        'iban_masked'    => null,
                        'account_holder' => $holderLabel,
                    ]);
                $sectionAccountMap[$section] = $sectionAcct->getKey();
            }
            // ── end account section map ─────────────────────────────────────────────

            foreach ($transactions as $tx) {
                try {
                    $payload    = $this->normalizeTransactionPayloadForInsert($tx);
                    $txSection  = (string) ((is_array($payload['meta'] ?? null) ? ($payload['meta']['account_section'] ?? null) : null) ?? 'joint');
                    $txAccountId = $sectionAccountMap[$txSection] ?? $bankAccount->getKey();

                    // Conservative cheque consolidation across overlapping statements:
                    // same account + date + type + cheque number = one physical cheque.
                    // Only use this DB-level consolidation when no explicit cleanup range exists.
                    if ($allowDbDedupHeuristics && ($payload['kind'] ?? null) === 'cheque' && !empty($payload['cheque_number'])) {
                        $existingCheque = Transaction::query()
                            ->where('bank_account_id', $txAccountId)
                            ->whereDate('date', $payload['date'])
                            ->where('type', $payload['type'])
                            ->where('kind', 'cheque')
                            ->where('cheque_number', $payload['cheque_number'])
                            ->orderBy('id')
                            ->first();

                        if ($existingCheque) {
                            $existingAbs = abs((float) $existingCheque->amount);
                            $newAbs = abs((float) $payload['amount']);

                            // Keep the most conservative amount when OCR conflicts
                            // (e.g. 30 000 vs 80 000 for same cheque number/date).
                            if ($newAbs < $existingAbs) {
                                $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];
                                $flags = is_array($meta['quality_flags'] ?? null) ? $meta['quality_flags'] : [];
                                $flags[] = 'cheque_overlap_consolidated';
                                $meta['quality_flags'] = array_values(array_unique($flags));

                                $existingCheque->forceFill([
                                    'label' => $payload['label'],
                                    'normalized_label' => $payload['normalized_label'],
                                    'amount' => $payload['amount'],
                                    'balance_after' => $payload['balance_after'],
                                    'beneficiary_detected' => $payload['beneficiary_detected'],
                                    'rule_flags' => $payload['rule_flags'],
                                    'origin' => $payload['origin'],
                                    'destination' => $payload['destination'],
                                    'motif' => $payload['motif'],
                                    'meta' => $meta,
                                ])->save();
                            }

                            // In both cases, do not insert a second cheque row.
                            continue;
                        }
                    }

                    if ($allowDbDedupHeuristics) {
                        $sameDayDuplicate = $this->findLikelySameDayDuplicate($txAccountId, $payload);
                        if ($sameDayDuplicate !== null) {
                            $keepNew = $this->shouldReplaceExistingWithPayload($sameDayDuplicate, $payload);

                            if ($keepNew) {
                                $sameDayDuplicate->forceFill([
                                    'date' => $payload['date'],
                                    'label' => $payload['label'],
                                    'normalized_label' => $payload['normalized_label'],
                                    'amount' => $payload['amount'],
                                    'type' => $payload['type'],
                                    'balance_after' => $payload['balance_after'],
                                    'beneficiary_detected' => $payload['beneficiary_detected'],
                                    'rule_flags' => $payload['rule_flags'],
                                    'kind' => $payload['kind'],
                                    'origin' => $payload['origin'],
                                    'destination' => $payload['destination'],
                                    'motif' => $payload['motif'],
                                    'cheque_number' => $payload['cheque_number'],
                                    'meta' => $payload['meta'],
                                ])->save();
                            }

                            $inserted++;
                            continue;
                        }

                        $fuzzyDuplicate = $this->findLikelyHighValueDuplicate($txAccountId, $payload);
                        if ($fuzzyDuplicate !== null) {
                            $keepNew = $this->shouldReplaceExistingWithPayload($fuzzyDuplicate, $payload);

                            if ($keepNew) {
                                $fuzzyDuplicate->forceFill([
                                    'date' => $payload['date'],
                                    'label' => $payload['label'],
                                    'normalized_label' => $payload['normalized_label'],
                                    'amount' => $payload['amount'],
                                    'type' => $payload['type'],
                                    'balance_after' => $payload['balance_after'],
                                    'beneficiary_detected' => $payload['beneficiary_detected'],
                                    'rule_flags' => $payload['rule_flags'],
                                    'kind' => $payload['kind'],
                                    'origin' => $payload['origin'],
                                    'destination' => $payload['destination'],
                                    'motif' => $payload['motif'],
                                    'cheque_number' => $payload['cheque_number'],
                                    'meta' => $payload['meta'],
                                ])->save();
                            }

                            $inserted++;
                            continue;
                        }

                        // ── Cross-year REFDO dedup ──────────────────────────────────────────
                        // When OCR misattributes the year (e.g. 2023-07-21 vs 2025-07-21),
                        // same-day checks miss it. If the payload carries a unique bank
                        // reference (REFDO / REFBEN) that matches an existing transaction
                        // of the same amount+type, treat them as the same transaction and
                        // keep the one with the date closest to the statement period.
                        $refdoDuplicate = $this->findRefDoDuplicate($txAccountId, $payload);
                        if ($refdoDuplicate !== null) {
                            $keepNew = $this->shouldReplaceExistingWithPayload($refdoDuplicate, $payload);
                            if ($keepNew) {
                                $refdoDuplicate->forceFill([
                                    'date' => $payload['date'],
                                    'label' => $payload['label'],
                                    'normalized_label' => $payload['normalized_label'],
                                    'amount' => $payload['amount'],
                                    'type' => $payload['type'],
                                    'balance_after' => $payload['balance_after'],
                                    'beneficiary_detected' => $payload['beneficiary_detected'],
                                    'rule_flags' => $payload['rule_flags'],
                                    'kind' => $payload['kind'],
                                    'origin' => $payload['origin'],
                                    'destination' => $payload['destination'],
                                    'motif' => $payload['motif'],
                                    'cheque_number' => $payload['cheque_number'],
                                    'meta' => $payload['meta'],
                                ])->save();
                            }
                            $inserted++;
                            continue;
                        }
                    }

                    Transaction::create([
                        'bank_account_id' => $txAccountId,
                        'date' => $payload['date'],
                        'label' => $payload['label'],
                        'normalized_label' => $payload['normalized_label'],
                        'amount' => $payload['amount'],
                        'type' => $payload['type'],
                        'balance_after' => $payload['balance_after'],
                        'beneficiary_detected' => $payload['beneficiary_detected'],
                        'rule_flags' => $payload['rule_flags'],
                        'kind' => $payload['kind'],
                        'origin' => $payload['origin'],
                        'destination' => $payload['destination'],
                        'motif' => $payload['motif'],
                        'cheque_number' => $payload['cheque_number'],
                        'meta' => $payload['meta'],
                    ]);
                    $inserted++;
                } catch (\Throwable $e) {
                    // Unique-key violations mean the transaction already exists
                    // (e.g. two overlapping statement PDFs). Treat as skipped, not as error.
                    if (str_contains($e->getMessage(), '23505') || str_contains($e->getMessage(), 'Unique violation') || str_contains($e->getMessage(), 'unique constraint')) {
                        $inserted++; // count as "handled"
                    } else {
                        $insertErrors++;
                        if ($firstInsertError === null) {
                            $firstInsertError = substr($e->getMessage(), 0, 300);
                        }

                        $this->recoverDatabaseConnectionAfterInsertError();
                    }
                }
            }

            if ($inserted === 0 && $transactions !== []) {
                $statement->forceFill([
                    'import_status' => 'failed',
                    'transactions_imported' => 0,
                    'import_error' => 'Transactions détectées mais aucune ligne insérée. Vérifie le format OCR/colonnes. Détail: '.($firstInsertError ?? 'n/a'),
                ])->save();

                return;
            }

            $importError = null;
            if ($insertErrors > 0) {
                $importError = sprintf('Import partiel: %d ligne(s) insérée(s), %d rejetée(s). %s', $inserted, $insertErrors, $firstInsertError ? 'Exemple: '.$firstInsertError : '');
            } elseif ($deletedExisting > 0) {
                $importError = sprintf('Réimport effectué: %d ancienne(s) ligne(s) remplacée(s) sur la période du relevé.', $deletedExisting);
            }

            $statement->forceFill([
                'import_status' => 'completed',
                'transactions_imported' => $inserted,
                'import_error' => $importError,
            ])->save();

            // ── Enrichissement LLM post-import ───────────────────────────────────────
            // Optionnel (ANALYTICA_AI_ENABLED=true + ANALYTICA_AI_ENRICHER_ENABLED=true).
            // Récupère les transactions insérées et demande au LLM de corriger/compléter
            // origin, destination, motif, kind pour chaque libellé OCR.
            if ((bool) config('analytica.ai.enricher_enabled', false) && (bool) config('analytica.ai.enabled', false)) {
                try {
                    $minDate = collect($transactions)->min('date');
                    $maxDate = collect($transactions)->max('date');

                    if ($minDate && $maxDate) {
                        $newTransactions = Transaction::query()
                            ->where('bank_account_id', $bankAccount->getKey())
                            ->whereBetween('date', [$minDate, $maxDate])
                            ->get();

                        $enricher->enrichTransactions($newTransactions);
                    }
                } catch (\Throwable $enrichEx) {
                    // Enrichissement non bloquant — import déjà marqué completed
                    \Illuminate\Support\Facades\Log::warning('[ImportStatementJob] Enrichissement LLM échoué', [
                        'statement_id' => $this->statementId,
                        'error'        => $enrichEx->getMessage(),
                    ]);
                }
            }

            if ((bool) config('analytica.import.auto_analyze', false)) {
                $engine->analyzeCase($case->fresh(['bankAccounts']));
            }
        } catch (\Throwable $e) {
            $statement->forceFill([
                'import_status' => 'failed',
                'import_error' => substr($e->getMessage(), 0, 2000),
            ])->save();
            throw $e;
        }
    }

    /**
     * Extrait le nom du titulaire depuis l'en-tête d'un relevé bancaire.
     * Gère les formats de toutes les banques françaises :
     * - BNP  : "M OU MME CHRISTIAN GIORDANO" / "M ET MME GIORDANO"
     * - CA   : "M. CHRISTIAN GIORDANO" / "MME LILIANE NOVAK"
     * - SG   : "MONSIEUR CHRISTIAN GIORDANO"
     * - LCL  : "M GIORDANO CHRISTIAN"
     */
    private function extractHolderFromText(string $text): ?string
    {
        // ── Priorité 1 : format BNP Succession ───────────────────────────────
        // "Succession de : M. CHRISTIAN GIORDANO né GIORDANO"
        if (preg_match('/Succession\s+de\s*:\s*(?:M(?:ONS(?:IEUR)?|ME|\.)?\.?|MME\.?|MADAME|MR\.?)\s+([A-ZÀÂÄÉÈÊËÏÎÔÙÛÜÇ][A-ZÀÂÄÉÈÊËÏÎÔÙÛÜÇ\- ]{1,50}?)(?:\s+n[eé]\b.*)?$/iu', $text, $m)) {
            $name = preg_replace('/\s+né\b.*/iu', '', trim($m[1]));
            $name = preg_replace('/\s+nee?\b.*/iu', '', $name);
            return mb_convert_case(preg_replace('/\s+/', ' ', trim($name)), MB_CASE_TITLE, 'UTF-8');
        }

        // ── Priorité 2 : en-tête des relevés courants (80 premières lignes) ──
        $upper = mb_strtoupper($text);
        $lines = array_slice(explode("\n", $upper), 0, 80);
        $cv = 'M(?:ONS(?:IEUR)?|ME|\.)?\.?|MME\.?|MADAME|MR\.?';

        foreach ($lines as $line) {
            $line = trim($line);
            if (strlen($line) < 5 || strlen($line) > 80) continue;

            // "M OU MME CHRISTIAN GIORDANO" (compte commun)
            if (preg_match('/^(?:' . $cv . ')\s+(?:OU|ET)\s+(?:' . $cv . ')\s+([A-ZÀÂÄÉÈÊËÏÎÔÙÛÜÇ][A-ZÀÂÄÉÈÊËÏÎÔÙÛÜÇ\- ]{2,50})$/u', $line, $m)) {
                return mb_convert_case(trim($m[1]), MB_CASE_TITLE, 'UTF-8');
            }

            // "M. CHRISTIAN GIORDANO" (prénom + nom)
            if (preg_match('/^(?:' . $cv . ')\s+([A-ZÀÂÄÉÈÊËÏÎÔÙÛÜÇ][A-Z]{1,20})\s+([A-ZÀÂÄÉÈÊËÏÎÔÙÛÜÇ][A-Z\-]{1,30})$/u', $line, $m)) {
                return mb_convert_case($m[1], MB_CASE_TITLE, 'UTF-8') . ' ' . mb_convert_case($m[2], MB_CASE_TITLE, 'UTF-8');
            }
        }

        return null;
    }

    /**
     * Extrait le RIB ou IBAN depuis le texte brut d'un relevé.
     * Gère les formats : "RIB : 30004 00702 00002123116 60" et "IBAN : FR76 3000 4007 ..."
     * Fonctionne pour toutes les banques françaises.
     */
    /**
     * Extrait l'intitulé complet du produit bancaire depuis l'en-tête du relevé.
     * Ex : "Relevé de compte chèques", "Relevé Livret Développement Durable et Solidaire"
     */
    private function extractAccountLabelFromText(string $text): ?string
    {
        $upper = mb_strtoupper($text);
        $lines = array_slice(explode("\n", $upper), 0, 80);

        // Dictionnaire de normalisation des labels connus
        $knownLabels = [
            'RELEVE DE COMPTE CHEQUES'                        => 'Relevé de compte chèques',
            'RELEVE DE COMPTE CHEQUE'                         => 'Relevé de compte chèques',
            'RELEVE COMPTE CHEQUES'                           => 'Relevé de compte chèques',
            'RELEVE LIVRET DEV DURABLE ET SOLIDAIRE'          => 'Relevé Livret Développement Durable et Solidaire',
            'RELEVE LIVRET DEVELOPPEMENT DURABLE ET SOLIDAIRE'=> 'Relevé Livret Développement Durable et Solidaire',
            'RELEVE LIVRET DEV. DURABLE ET SOLIDAIRE'         => 'Relevé Livret Développement Durable et Solidaire',
            'RELEVE LIVRET EPARGNE'                           => 'Relevé Livret Épargne',
            'RELEVE LIVRET A'                                 => 'Relevé Livret A',
            'RELEVE COMPTE COURANT'                           => 'Relevé de compte courant',
            'RELEVE DE COMPTE COURANT'                        => 'Relevé de compte courant',
            'RELEVE DE COMPTE ESPECES'                        => 'Relevé de compte espèces',
        ];

        $patterns = [
            '/RELEV[EÉ]\s+(?:DE\s+)?(?:COMPTE\s+)?.{5,60}?(?=\s+P\.?\s*\d|\s*$)/u',
        ];

        foreach ($lines as $line) {
            $line = trim($line);
            if (strlen($line) < 10 || strlen($line) > 120) continue;
            if (!str_contains($line, 'RELEV')) continue;

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $line, $m)) {
                    $raw = preg_replace('/\s+P\.?\s*\d+.*$/u', '', trim($m[0]));
                    $raw = trim(preg_replace('/\s+/u', ' ', $raw));
                    if (strlen($raw) < 10) continue;

                    // Chercher dans le dictionnaire (correspondance partielle)
                    foreach ($knownLabels as $key => $value) {
                        if (str_contains($raw, $key)) {
                            return $value;
                        }
                    }

                    // Correspondance exacte
                    $clean = preg_replace('/[ÈÉÊ]/u', 'E', mb_strtoupper($raw));
                    foreach ($knownLabels as $key => $value) {
                        if (str_contains($clean, $key)) {
                            return $value;
                        }
                    }

                    // Fallback : retourner brut en title case si raisonnable
                    if (strlen($raw) >= 10 && strlen($raw) <= 100) {
                        return mb_convert_case(mb_strtolower($raw), MB_CASE_TITLE, 'UTF-8');
                    }
                }
            }
        }

        return null;
    }

    private function extractRibFromText(string $text): ?string
    {
        $upper = mb_strtoupper($text);

        // RIB français : 5 chiffres banque + 5 chiffres guichet + 11 chiffres compte + 2 chiffres clé
        // Format "RIB : 30004 00702 00002123116 60" ou "RIB 30004 00702 00002123116 60"
        if (preg_match('/\bRIB\s*[:\-]?\s*([0-9\s]{20,30})\b/u', $upper, $m)) {
            $rib = preg_replace('/\s+/', ' ', trim($m[1]));
            // Valider : doit contenir exactement 23 chiffres au total
            $digits = preg_replace('/\D/', '', $rib);
            if ($digits !== null && strlen($digits) === 23) {
                return $rib;
            }
        }

        // IBAN français : FR + 2 chiffres + jusqu'à 23 caractères alphanumériques
        if (preg_match('/\bIBAN\s*[:\-]?\s*(FR\s*\d{2}(?:\s*[A-Z0-9]{4}){4,6}(?:\s*[A-Z0-9]{0,3})?)\b/u', $upper, $m)) {
            $iban = preg_replace('/\s+/', ' ', trim($m[1]));
            return $iban;
        }

        // IBAN sans label (ligne contenant uniquement un IBAN dans les 30 premières lignes)
        $lines = array_slice(explode("\n", $upper), 0, 50);
        foreach ($lines as $line) {
            if (preg_match('/\b(FR\d{2}(?:\s*[A-Z0-9]){18,27})\b/u', $line, $m)) {
                $candidate = preg_replace('/\s+/', ' ', trim($m[1]));
                $noSpaces = preg_replace('/\s/', '', $candidate) ?? '';
                if (strlen($noSpaces) >= 14 && strlen($noSpaces) <= 34) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    private function extractPdfText(string $pdfBytes): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'analytica_pdf_');
        if ($tmp === false) {
            throw new \RuntimeException('Unable to create temp file for PDF parsing.');
        }

        $path = $tmp.'.pdf';
        @rename($tmp, $path);
        file_put_contents($path, $pdfBytes);

        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($path);
            $text = $pdf->getText();
            $text = trim((string) $text);

            return $text;
        } finally {
            @unlink($path);
        }
    }

    /**
     * Count pages in a PDF using smalot PdfParser.
     */
    private function getPdfPageCount(string $pdfBytes): int
    {
        $tmp = tempnam(sys_get_temp_dir(), 'analytica_pc_');
        if ($tmp === false) {
            return 1;
        }
        $path = $tmp.'.pdf';
        @rename($tmp, $path);
        file_put_contents($path, $pdfBytes);
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($path);
            return max(1, count($pdf->getPages()));
        } catch (\Throwable) {
            return 1;
        } finally {
            @unlink($path);
        }
    }

    /**
     * OCR a PDF by splitting it into batches of $batchSize pages and concatenating the results.
     * This is required for large PDFs (100+ pages) since the OCR server caps at 50 pages per call.
     */
    private function ocrPdfFull(
        string $pdfBytes,
        int $totalPages,
        int $dpi,
        int $psm,
        bool $preprocess,
        int $batchSize = 40,
    ): string {
        $texts = [];
        for ($start = 1; $start <= $totalPages; $start += $batchSize) {
            $pagesToOcr = min($batchSize, $totalPages - $start + 1);
            $chunk = $this->ocrPdf($pdfBytes, $start, $pagesToOcr, $dpi, $psm, $preprocess);
            if ($chunk !== '') {
                $texts[] = $chunk;
            }
        }
        return implode("\n", $texts);
    }

    private function ocrPdf(string $pdfBytes, int $firstPage, int $maxPages, int $dpi, int $psm, bool $preprocess): string
    {
        $url = (string) config('analytica.ocr_url', env('ANALYTICA_OCR_URL'));
        if ($url === '') {
            return '';
        }

        $lang = (string) env('ANALYTICA_OCR_LANG', 'fra');

        $ocrMaxPagesLimit = (int) env('ANALYTICA_OCR_MAX_PAGES_LIMIT', 260);

        $response = Http::connectTimeout(10)
            ->timeout((int) env('ANALYTICA_OCR_TIMEOUT', 300))
            ->retry(1, 500)
            ->attach('file', $pdfBytes, 'statement.pdf')
            ->post($url, [
                'lang' => $lang,
                'first_page' => max(1, $firstPage),
                'max_pages' => max(1, min($ocrMaxPagesLimit, $maxPages)),
                'dpi' => max(150, min(350, $dpi)),
                'psm' => max(3, min(12, $psm)),
                'preprocess' => $preprocess ? 1 : 0,
            ]);

        if (! $response->successful()) {
            return '';
        }

        return (string) ($response->json('text') ?? '');
    }

    /**
     * @return array<int, array{first_page:int,max_pages:int,dpi:int,psm:int,preprocess:bool}>
     */
    private function buildOcrProfiles(int $firstPage): array
    {
        $expertMode = ((string) config('analytica.import.mode', 'expert')) === 'expert';
        $baseMaxPages = (int) env('ANALYTICA_OCR_MAX_PAGES', 24);
        $baseDpi = (int) env('ANALYTICA_OCR_DPI', 250);
        $basePsm = (int) env('ANALYTICA_OCR_PSM', 6);
        $hardCap = (int) env('ANALYTICA_OCR_MAX_PAGES_LIMIT', 260);

        if ($expertMode) {
            $baseMaxPages = max($baseMaxPages, 180);
            $baseDpi = max($baseDpi, 300);
        }

        $profiles = [
            [
                'first_page' => $expertMode ? 1 : max(1, $firstPage),
                'max_pages' => max(1, min($hardCap, $baseMaxPages)),
                'dpi' => max(150, min(350, $baseDpi)),
                'psm' => max(3, min(12, $basePsm)),
                'preprocess' => true,
            ],
            [
                'first_page' => max(1, $firstPage),
                'max_pages' => max(1, min($hardCap, max($baseMaxPages, 48))),
                'dpi' => max(150, min(350, max($baseDpi, 300))),
                'psm' => 6,
                'preprocess' => true,
            ],
            [
                'first_page' => $expertMode ? 1 : max(1, $firstPage - 1),
                'max_pages' => max(1, min($hardCap, max($baseMaxPages, 64))),
                'dpi' => 300,
                'psm' => 4,
                'preprocess' => true,
            ],
            [
                'first_page' => $expertMode ? 1 : max(1, $firstPage),
                'max_pages' => max(1, min($hardCap, $baseMaxPages)),
                'dpi' => max(150, min(350, $baseDpi)),
                'psm' => max(3, min(12, $basePsm)),
                'preprocess' => false,
            ],
        ];

        if ($expertMode) {
            $profiles[] = [
                'first_page' => 1,
                'max_pages' => max(1, min($hardCap, max($baseMaxPages, 180))),
                'dpi' => 350,
                'psm' => 6,
                'preprocess' => true,
            ];
            $profiles[] = [
                'first_page' => 1,
                'max_pages' => max(1, min($hardCap, max($baseMaxPages, 220))),
                'dpi' => 350,
                'psm' => 4,
                'preprocess' => true,
            ];
        }

        return $profiles;
    }

    /**
     * @param array<int, array<string,mixed>> $transactions
     */
    private function isLikelyIncompletePdfParse(string $text, array $transactions): bool
    {
        $txCount = count($transactions);
        if ($txCount === 0) {
            return true;
        }

        $estimatedPages = null;
        if (preg_match_all('/\bP\.?\s*\d{1,4}\s*[\/-]\s*(\d{1,4})\b/u', mb_strtoupper($text), $m)) {
            $denominators = array_map('intval', $m[1] ?? []);
            if ($denominators !== []) {
                $estimatedPages = max($denominators);
            }
        }

        if ($estimatedPages === null) {
            return false;
        }

        $minTxPerPage = (float) env('ANALYTICA_OCR_MIN_TX_PER_PAGE', 4);
        $expectedMin = (int) floor($estimatedPages * $minTxPerPage);

        return $estimatedPages >= 30 && $txCount < max(80, $expectedMin);
    }

    private function scoreOcrCandidate(string $ocrText, array $transactions): int
    {
        $amountLikeMatches = [];
        preg_match_all('/\b\d{1,3}(?:[\s.]\d{3})*[\.,]\d{2}\b/u', $ocrText, $amountLikeMatches);
        $amountTokenCount = count($amountLikeMatches[0] ?? []);

        $txCount = count($transactions);

        return ($txCount * 1000) + ($amountTokenCount * 2) + min(500, intdiv(mb_strlen($ocrText), 20));
    }

    private function shouldAttemptOcr(string $extractedText): bool
    {
        // Even if the first pages look like a cover letter, later pages can contain the scanned statement table.
        // We mitigate cost by limiting OCR to a small page range.
        return true;
    }

    private function guessOcrFirstPage(string $extractedText): int
    {
        $configured = (int) env('ANALYTICA_OCR_FIRST_PAGE', 3);
        if ($configured >= 1) {
            return $configured;
        }

        $t = mb_strtoupper($extractedText);
        if (str_contains($t, 'RELEVE DE COMPTE') || str_contains($t, 'RELEVÉ DE COMPTE')) {
            return 1;
        }

        // Common pattern: page 1 cover letter, page 2 blank, table starts around page 3.
        return 3;
    }

    private function buildNoTransactionsMessage(string $extractedText, bool $ocrAttempted, ?string $ocrError): string
    {
        $t = mb_strtoupper($extractedText);

        if ($ocrAttempted && $ocrError) {
            return 'Aucune transaction détectée. OCR indisponible ou trop lent (timeout). Réessaie avec un PDF plus léger ou un export CSV.';
        }

        // Generic fallback.
        if (str_contains($t, 'RELEVE') || str_contains($t, 'RELEVÉ')) {
            return 'Aucune transaction détectée dans ce PDF. Si c\'est un relevé scanné, l\'OCR peut être nécessaire (ou utilise un export CSV).';
        }

        return 'Aucune transaction détectée dans ce fichier.';
    }

    private function guessDefaultYear(?string $filename, string $text): ?int
    {
        // === PRIORITY 1: Year from OCR text header (most reliable) ===
        // French bank statements always start with "du DD mois YYYY au DD mois YYYY".
        // Try to extract it from the first 2 000 characters where the header lives.
        $headerArea = mb_substr($text, 0, 2000);
        $normalized = mb_strtolower($headerArea);
        $normalized = preg_replace('/\s+/u', ' ', str_replace(["\n", "\r"], ' ', $normalized));

        // "du 22 février 2021 au 22 mars 2021" — month may be OCR-corrupted (apostrophes, accents)
        if (preg_match('/\bdu\s+\d{1,2}\s+[^\s\d]+\s+(20\d{2})\b/u', $normalized, $m)) {
            return (int) $m[1];
        }

        // "du 22/02/2021 au 22/03/2021" or "du 22.02.2021 au 22.03.2021"
        if (preg_match('/\bdu\s+\d{1,2}[\/\-.]\d{1,2}[\/\-.](20\d{2})\b/u', $normalized, $m)) {
            return (int) $m[1];
        }

        // Any 4-digit year 20xx in the first 500 chars (where the header title is)
        if (preg_match('/\b(20[012]\d)\b/u', mb_substr($text, 0, 500), $m)) {
            return (int) $m[1];
        }

        // === PRIORITY 2: Filename — but ONLY if it does NOT look like a timestamp ===
        // Timestamps look like "20260108115729" (14+ digit run with time).
        // A simple statement year looks like "2021" or "2021_" but not "20260108".
        if (is_string($filename)) {
            // Strip timestamps (YYYYMMDDHHMMSS or YYYYMMDD embedded in long digit run).
            $filenameClean = preg_replace('/20\d{6,12}/', '', $filename) ?? $filename;
            if (preg_match('/\b(20[012]\d)\b/', $filenameClean, $m)) {
                return (int) $m[1];
            }
        }

        // === PRIORITY 3: Most-common 4-digit year in the full text ===
        if (preg_match_all('/\b(20[012]\d)\b/u', $text, $m)) {
            $counts = array_count_values($m[1]);
            arsort($counts);
            $year = (int) array_key_first($counts);
            if ($year >= 2000 && $year <= (int) now()->format('Y')) {
                return $year;
            }
        }

        return null; // caller will fall back to current year if null
    }

    private function normalizeTransactionPayloadForInsert(array $tx): array
    {
        return [
            'date' => $tx['date'],
            'label' => (string) ($tx['label'] ?? ''),
            'normalized_label' => (string) ($tx['normalized_label'] ?? ''),
            'amount' => $tx['amount'],
            'type' => $this->truncateNullable((string) ($tx['type'] ?? ''), 255),
            'balance_after' => $tx['balance_after'],
            'beneficiary_detected' => (bool) ($tx['beneficiary_detected'] ?? false),
            'rule_flags' => $tx['rule_flags'] ?? [],
            'kind' => $this->truncateNullable($tx['kind'] ?? null, 255),
            'origin' => $this->truncateNullable($tx['origin'] ?? null, 255),
            'destination' => $this->truncateNullable($tx['destination'] ?? null, 255),
            'motif' => $tx['motif'] ?? null,
            'cheque_number' => $this->truncateNullable($tx['cheque_number'] ?? null, 255),
            'meta' => $tx['meta'] ?? [],
        ];
    }

    private function findLikelyHighValueDuplicate(int $bankAccountId, array $payload): ?Transaction
    {
        $absAmount = abs((float) ($payload['amount'] ?? 0));
        $threshold = (float) config('analytica.import.high_value_threshold', 20000);

        if ($absAmount < $threshold) {
            return null;
        }

        $date = (string) ($payload['date'] ?? '');
        $type = (string) ($payload['type'] ?? '');
        $kind = (string) ($payload['kind'] ?? '');
        $normalizedLabel = (string) ($payload['normalized_label'] ?? '');

        if ($date === '' || $type === '' || $normalizedLabel === '') {
            return null;
        }

        $candidates = Transaction::query()
            ->where('bank_account_id', $bankAccountId)
            ->where('type', $type)
            ->where('kind', $kind)
            ->whereRaw('ABS(amount) = ?', [$absAmount])
            ->whereDate('date', $date)
            ->orderByDesc('id')
            ->get();

        foreach ($candidates as $candidate) {
            $candidateLabel = (string) ($candidate->normalized_label ?? '');
            // High-value: date+amount+type already very constraining — use lenient label match
            if (!$this->labelsLikelySameTransactionForImport($candidateLabel, $normalizedLabel, true)) {
                continue;
            }

            return $candidate;
        }

        return null;
    }

    /**
     * Detect cross-year duplicates caused by OCR year-attribution errors.
     * Two transactions with the same REFDO bank reference, same amount, and same type
     * are the same physical movement regardless of which year the parser assigned.
     * The REFDO is a 20-40 char hex/alphanumeric token following "REFDO" in the label.
     */
    private function findRefDoDuplicate(int $bankAccountId, array $payload): ?Transaction
    {
        $normalizedLabel = mb_strtoupper((string) ($payload['normalized_label'] ?? $payload['label'] ?? ''));
        $type            = (string) ($payload['type'] ?? '');
        $absAmount       = abs((float) ($payload['amount'] ?? 0));

        if ($absAmount <= 0 || $type === '' || $normalizedLabel === '') {
            return null;
        }

        // Extract REFDO token: alphanumeric sequence of 16+ chars after REFDO
        if (!preg_match('/\bREFDO\s+([A-Z0-9]{16,})/i', $normalizedLabel, $m)) {
            return null;
        }
        $refdo = $m[1];

        // Strip leading OCR-noise chars (single letter prefix like 'A' before a hex string)
        $refdoCore = preg_replace('/^[A-F]{1,2}(?=[0-9A-F]{16})/i', '', $refdo);

        $candidates = Transaction::query()
            ->where('bank_account_id', $bankAccountId)
            ->where('type', $type)
            ->whereRaw('ABS(amount) = ?', [$absAmount])
            ->whereRaw("normalized_label ILIKE ? OR normalized_label ILIKE ?",
                ['%REFDO%'.$refdo.'%', '%REFDO%'.$refdoCore.'%'])
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return $candidates->first() ?: null;
    }

    private function findLikelySameDayDuplicate(int $bankAccountId, array $payload): ?Transaction
    {
        $date = (string) ($payload['date'] ?? '');
        $type = (string) ($payload['type'] ?? '');
        $normalizedLabel = (string) ($payload['normalized_label'] ?? '');
        $label = (string) ($payload['label'] ?? '');
        $absAmount = abs((float) ($payload['amount'] ?? 0));

        if ($date === '' || $type === '' || $absAmount <= 0 || ($normalizedLabel === '' && $label === '')) {
            return null;
        }

        $candidates = Transaction::query()
            ->where('bank_account_id', $bankAccountId)
            ->whereDate('date', $date)
            ->where('type', $type)
            ->whereRaw('ABS(amount) = ?', [$absAmount])
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        foreach ($candidates as $candidate) {
            $candidateLabel = (string) ($candidate->normalized_label ?? $candidate->label ?? '');
            $incomingLabel = $normalizedLabel !== '' ? $normalizedLabel : $label;

            if (!$this->labelsVeryLikelySameTransactionForImport($candidateLabel, $incomingLabel)) {
                if (!$this->sameDayEvidenceSuggestsOverlap($candidate, $payload)) {
                    continue;
                }
            }

            return $candidate;
        }

        return null;
    }

    private function sameDayEvidenceSuggestsOverlap(Transaction $candidate, array $payload): bool
    {
        if ($this->sameBalanceAfter($candidate, $payload)) {
            return true;
        }

        $candidateStructured = Normalization::normalizeLabel(implode(' ', array_filter([
            (string) ($candidate->origin ?? ''),
            (string) ($candidate->destination ?? ''),
            (string) ($candidate->motif ?? ''),
            (string) ($candidate->cheque_number ?? ''),
        ])));

        $payloadStructured = Normalization::normalizeLabel(implode(' ', array_filter([
            (string) ($payload['origin'] ?? ''),
            (string) ($payload['destination'] ?? ''),
            (string) ($payload['motif'] ?? ''),
            (string) ($payload['cheque_number'] ?? ''),
        ])));

        if ($candidateStructured !== '' && $payloadStructured !== '') {
            if ($candidateStructured === $payloadStructured) {
                return true;
            }

            if (mb_strlen($candidateStructured) >= 12 && mb_strlen($payloadStructured) >= 12) {
                $maxLen = max(mb_strlen($candidateStructured), mb_strlen($payloadStructured), 1);
                $distance = levenshtein(mb_substr($candidateStructured, 0, 255), mb_substr($payloadStructured, 0, 255));

                if (($distance / $maxLen) <= 0.22) {
                    return true;
                }
            }
        }

        return false;
    }

    private function sameBalanceAfter(Transaction $candidate, array $payload): bool
    {
        $existing = $candidate->balance_after;
        $incoming = $payload['balance_after'] ?? null;

        if ($existing === null || $incoming === null || $incoming === '') {
            return false;
        }

        return abs((float) $existing - (float) $incoming) < 0.005;
    }

    private function shouldReplaceExistingWithPayload(Transaction $existing, array $payload): bool
    {
        $existingScore = $this->computeImportReliabilityScore(
            optional($existing->date)->toDateString(),
            is_array($existing->meta ?? null) ? $existing->meta : []
        );

        $payloadScore = $this->computeImportReliabilityScore(
            (string) ($payload['date'] ?? ''),
            is_array($payload['meta'] ?? null) ? $payload['meta'] : []
        );

        if ($payloadScore === $existingScore) {
            return (string) ($payload['date'] ?? '') >= (string) optional($existing->date)->toDateString();
        }

        return $payloadScore > $existingScore;
    }

    private function computeImportReliabilityScore(?string $date, array $meta): int
    {
        $score = (int) ($meta['confidence'] ?? 0);

        $flags = is_array($meta['quality_flags'] ?? null) ? $meta['quality_flags'] : [];
        if (in_array('outside_statement_period', $flags, true)) {
            $score -= 30;
        }

        if ($date !== '' && is_array($meta['statement_period'] ?? null)) {
            $period = $meta['statement_period'];
            $start = (string) ($period['start'] ?? '');
            $end = (string) ($period['end'] ?? '');

            if ($start !== '' && $end !== '') {
                try {
                    $txDate = Carbon::parse($date);
                    $windowStart = Carbon::parse($start)->subDays(10);
                    $windowEnd = Carbon::parse($end)->addDays(10);

                    if ($txDate->between($windowStart, $windowEnd)) {
                        $score += 20;
                    } else {
                        $score -= 20;
                    }
                } catch (\Throwable) {
                    $score -= 10;
                }
            }
        }

        return $score;
    }

    /**
     * @param bool $lenient When true (high-value transactions), use a wider similarity tolerance (0.35)
     *                      because date + exact amount + type already give very high confidence.
     */
    private function labelsLikelySameTransactionForImport(string $left, string $right, bool $lenient = false): bool
    {
        $leftCanonical = $this->canonicalizeLabelForImportDedup($left);
        $rightCanonical = $this->canonicalizeLabelForImportDedup($right);

        if ($leftCanonical === '' || $rightCanonical === '') {
            return false;
        }

        if ($leftCanonical === $rightCanonical) {
            return true;
        }

        $leftLen = mb_strlen($leftCanonical);
        $rightLen = mb_strlen($rightCanonical);
        $minLen = min($leftLen, $rightLen);

        if ($minLen >= 18 && (str_contains($leftCanonical, $rightCanonical) || str_contains($rightCanonical, $leftCanonical))) {
            return true;
        }

        $maxLen = max($leftLen, $rightLen, 1);
        $distance = levenshtein(mb_substr($leftCanonical, 0, 255), mb_substr($rightCanonical, 0, 255));
        $threshold = $lenient ? 0.35 : 0.18;

        return ($distance / $maxLen) <= $threshold;
    }

    private function labelsVeryLikelySameTransactionForImport(string $left, string $right): bool
    {
        $leftRaw = mb_strtoupper(trim($left));
        $rightRaw = mb_strtoupper(trim($right));

        if ($leftRaw === '' || $rightRaw === '') {
            return false;
        }

        if ($leftRaw === $rightRaw) {
            return true;
        }

        $leftRawLen = mb_strlen($leftRaw);
        $rightRawLen = mb_strlen($rightRaw);
        $rawMinLen = min($leftRawLen, $rightRawLen);
        if ($rawMinLen >= 18 && (str_contains($leftRaw, $rightRaw) || str_contains($rightRaw, $leftRaw))) {
            return true;
        }

        $leftCanonical = $this->canonicalizeLabelForImportDedup($leftRaw);
        $rightCanonical = $this->canonicalizeLabelForImportDedup($rightRaw);

        if ($leftCanonical === '' || $rightCanonical === '') {
            return false;
        }

        if ($leftCanonical === $rightCanonical) {
            return true;
        }

        $leftLen = mb_strlen($leftCanonical);
        $rightLen = mb_strlen($rightCanonical);
        $minLen = min($leftLen, $rightLen);
        if ($minLen >= 20 && (str_contains($leftCanonical, $rightCanonical) || str_contains($rightCanonical, $leftCanonical))) {
            return true;
        }

        $maxLen = max($leftLen, $rightLen, 1);
        $distance = levenshtein(mb_substr($leftCanonical, 0, 255), mb_substr($rightCanonical, 0, 255));

        return ($distance / $maxLen) <= 0.35;
    }

    private function canonicalizeLabelForImportDedup(string $label): string
    {
        $normalized = mb_strtoupper(trim($label));
        // Strip leading day.month prefix (e.g. "16.02 " or "16 02 ")
        $normalized = preg_replace('/^\d{1,2}[.\s]\d{2}\s+/u', '', $normalized) ?? $normalized;
        // Strip date range patterns: DU xx MONTH ER? AU xx MONTH ER? (OCR artefacts)
        $normalized = preg_replace('/\bDU\s+\d{1,2}\s+\w+\s*(ER)?\s+\d{0,4}\s+AU\s+\d{1,2}\s+\w+\s*(ER)?\s+\d{0,4}\b/u', ' ', $normalized) ?? $normalized;
        // Strip town / person suffix after GARDANNE
        $normalized = preg_replace('/\bGARDANNE\b.*/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\bBNP\s+PARIBAS\s+SA\b.*$/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\b(?:RCS|ORIAS|SIEGE|SI[EÈ]GE|SERVICE\s+CLIENT|MONNAIE\s+DU\s+COMPTE)\b.*$/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\b(?:REF|REFDO|REFBEN|EMETTEUR|EMETTEUR\/|MDT|IBAN|BIC|RIB|LIB|MOT|MOTIF)\b[^\n]*/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\b[A-Z0-9]{10,}\b/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\b\d+\b/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }

    /**
     * @param array<int, array<string,mixed>> $transactions
     * @return array{start:string,end:string}|null
     */
    private function resolveCleanupRange(array $transactions): ?array
    {
        if ($transactions === []) {
            return null;
        }

        $periodStart = null;
        $periodEnd = null;

        foreach ($transactions as $tx) {
            $period = $tx['meta']['statement_period'] ?? null;
            if (!is_array($period)) {
                continue;
            }

            $start = (string) ($period['start'] ?? '');
            $end = (string) ($period['end'] ?? '');
            if ($start === '' || $end === '') {
                continue;
            }

            $periodStart = $start;
            $periodEnd = $end;
            break;
        }

        if ($periodStart !== null && $periodEnd !== null) {
            return ['start' => $periodStart, 'end' => $periodEnd];
        }

        $dates = array_values(array_filter(array_map(function ($tx) {
            $d = (string) ($tx['date'] ?? '');
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) ? $d : null;
        }, $transactions)));

        if ($dates === []) {
            return null;
        }

        sort($dates);
        $start = $dates[0];
        $end = $dates[count($dates) - 1];

        try {
            $startDate = new \DateTimeImmutable($start);
            $endDate = new \DateTimeImmutable($end);
            $days = (int) $startDate->diff($endDate)->days;
            if ($days <= 62) {
                return ['start' => $start, 'end' => $end];
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function truncateNullable(?string $value, int $max): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $clean = trim($value);
        if ($clean === '') {
            return null;
        }

        if (mb_strlen($clean) <= $max) {
            return $clean;
        }

        return mb_substr($clean, 0, $max);
    }

    private function recoverDatabaseConnectionAfterInsertError(): void
    {
        try {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        } catch (\Throwable) {
        }

        try {
            DB::disconnect();
            DB::reconnect();
        } catch (\Throwable) {
        }
    }
}
