<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Enrichit les champs origin / destination / motif / kind des transactions
 * en envoyant leurs libellés bruts (souvent OCR imparfait) à un LLM.
 *
 * Traite les transactions en lots de BATCH_SIZE pour limiter les appels API.
 * Ne remplace un champ que s'il est actuellement vide (non-destructif).
 */
class LlmEnricher
{
    private const BATCH_SIZE = 25;

    private const VALID_KINDS = [
        'transfer', 'card', 'cheque', 'cash_withdrawal',
        'direct_debit', 'levy', 'salary', 'rental',
        'loan', 'tax', 'insurance', 'remittance', 'other',
    ];

    private const SYSTEM_PROMPT = <<<'TXT'
Tu analyses des libellés de transactions bancaires françaises, souvent issus d'OCR imparfait (lettres manquantes, substitutions, fragmentations).

Pour chaque transaction fournie, extrais en JSON:
- "origin"      : personne ou entité qui émet le paiement (null si vraiment inconnu)
- "destination" : personne ou entité qui reçoit le paiement (null si vraiment inconnu)
- "motif"       : raison/objet du paiement en clair (null si inconnu)
- "kind"        : catégorie parmi: transfer, card, cheque, cash_withdrawal, direct_debit, levy, salary, rental, loan, tax, insurance, remittance, other

RÈGLES CRITIQUES:
1. Pour un débit (amount < 0): origin = null (le titulaire est toujours implicitement l'émetteur, ne l'écris jamais), destination = bénéficiaire explicite du libellé
2. Pour un crédit (amount > 0): origin = émetteur explicite du libellé, destination = null (le titulaire est toujours implicitement le destinataire, ne l'écris jamais)
3. Corrige les OCR évidents: GORDANO→GIORDANO, G:ORDANO→GIORDANO, MiLLIET→MILLIET, LiL£ANE→LILIANE, :→I, !→I, 0→O quand dans un nom
4. Les marqueurs BNP /DE /BEN /MOTIF /REFDO /REF délimitent les champs:
   - "RECU /DE X /MOTIF Y" → origin=X, motif=Y
   - "EMIS /BEN X /MOTIF Y" → destination=X, motif=Y
5. Pour "VIR CPTE A CPTE" entre comptes du même titulaire, kind=transfer
6. Pour "RETRAIT DAB" ou "RETRAIT CB", kind=cash_withdrawal
7. Pour "FACTURE(S) CARTE" ou "CB", kind=card
8. Pour "PRLV SEPA", kind=direct_debit
9. Pour "SALAIRE" ou "TRAITEMENT", kind=salary
10. Ne fabrique AUCUNE information absente du libellé. Si incertain, mettre null.

Réponds STRICTEMENT en JSON valide: {"results": [{"id": <int>, "origin": <str|null>, "destination": <str|null>, "motif": <str|null>, "kind": <str>}, ...]}
TXT;

    public function enrichTransactions(Collection $transactions): int
    {
        if (!(bool) config('analytica.ai.enabled', false)) {
            return 0;
        }

        $apiKey = (string) env('OPENAI_API_KEY', '');
        if ($apiKey === '') {
            Log::debug('[LlmEnricher] OPENAI_API_KEY absente, enrichissement ignoré.');
            return 0;
        }

        if (!(bool) config('analytica.ai.enricher_enabled', true)) {
            return 0;
        }

        $needsEnrichment = $transactions->filter(fn ($tx) => $this->needsEnrichment($tx));

        if ($needsEnrichment->isEmpty()) {
            return 0;
        }

        $updated = 0;

        foreach ($needsEnrichment->chunk(self::BATCH_SIZE) as $batch) {
            $updated += $this->processBatch($batch, $apiKey);
        }

        return $updated;
    }

    /**
     * Ré-enrichit les transactions d'une collection même si elles ont déjà des valeurs.
     * Utilisé pour la re-correction forcée (ex: commande artisan --force).
     */
    public function forceEnrichTransactions(Collection $transactions): int
    {
        if (!(bool) config('analytica.ai.enabled', false)) {
            return 0;
        }

        $apiKey = (string) env('OPENAI_API_KEY', '');
        if ($apiKey === '') {
            return 0;
        }

        $updated = 0;

        foreach ($transactions->chunk(self::BATCH_SIZE) as $batch) {
            $updated += $this->processBatch($batch, $apiKey, force: true);
        }

        return $updated;
    }

    private function needsEnrichment(Transaction $tx): bool
    {
        return empty($tx->origin)
            || empty($tx->destination)
            || empty($tx->motif)
            || ($tx->kind ?? 'other') === 'other';
    }

    private function processBatch(Collection $batch, string $apiKey, bool $force = false): int
    {
        $baseUrl = rtrim((string) config('analytica.ai.openai_base_url', 'https://api.openai.com/v1'), '/');
        $model   = (string) config('analytica.ai.enricher_model', 'gpt-4.1-mini');
        $timeout = (int) config('analytica.ai.enricher_timeout_seconds', 30);

        $items = $batch->values()->map(fn ($tx) => [
            'id'     => $tx->getKey(),
            'label'  => (string) ($tx->normalized_label ?? ''),
            'amount' => (float) ($tx->amount ?? 0),
            'type'   => (string) ($tx->type ?? ''),
        ])->values()->all();

        try {
            $resp = Http::baseUrl($baseUrl)
                ->withToken($apiKey)
                ->timeout($timeout)
                ->acceptJson()
                ->asJson()
                ->post('/chat/completions', [
                    'model'           => $model,
                    'temperature'     => 0.0,
                    'response_format' => ['type' => 'json_object'],
                    'messages'        => [
                        ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                        ['role' => 'user',   'content' => json_encode(
                            ['transactions' => $items],
                            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                        )],
                    ],
                ]);
        } catch (ConnectionException $e) {
            Log::warning('[LlmEnricher] Connexion échouée', ['error' => $e->getMessage()]);
            return 0;
        }

        if (!$resp->successful()) {
            Log::warning('[LlmEnricher] Réponse HTTP non-200', [
                'status' => $resp->status(),
                'body'   => mb_substr((string) $resp->body(), 0, 300),
            ]);
            return 0;
        }

        $content = (string) ($resp->json('choices.0.message.content') ?? '');
        $decoded = json_decode($content, true);

        if (!is_array($decoded)) {
            Log::warning('[LlmEnricher] JSON non parseable', ['content_preview' => mb_substr($content, 0, 300)]);
            return 0;
        }

        $results = $decoded['results'] ?? [];
        if (!is_array($results)) {
            return 0;
        }

        /** @var array<int, array<string, mixed>> $resultsByTxId */
        $resultsByTxId = collect($results)->keyBy('id');

        $updated = 0;

        foreach ($batch as $tx) {
            $enriched = $resultsByTxId->get($tx->getKey());
            if (!is_array($enriched)) {
                continue;
            }

            $changed = false;

            $origin      = isset($enriched['origin'])      && is_string($enriched['origin'])      ? trim($enriched['origin'])      : null;
            $destination = isset($enriched['destination']) && is_string($enriched['destination']) ? trim($enriched['destination']) : null;
            $motif       = isset($enriched['motif'])       && is_string($enriched['motif'])       ? trim($enriched['motif'])       : null;
            $kind        = isset($enriched['kind'])        && is_string($enriched['kind'])        ? trim($enriched['kind'])        : null;

            if ($origin !== null && ($force || empty($tx->origin))) {
                $tx->origin = $origin;
                $changed = true;
            }
            if ($destination !== null && ($force || empty($tx->destination))) {
                $tx->destination = $destination;
                $changed = true;
            }
            if ($motif !== null && ($force || empty($tx->motif))) {
                $tx->motif = $motif;
                $changed = true;
            }
            if ($kind !== null && in_array($kind, self::VALID_KINDS, true) && ($force || ($tx->kind ?? 'other') === 'other')) {
                $tx->kind = $kind;
                $changed = true;
            }

            if ($changed) {
                $tx->save();
                $updated++;
            }
        }

        return $updated;
    }
}
