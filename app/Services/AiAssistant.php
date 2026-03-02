<?php

namespace App\Services;

use App\Models\CaseFile;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class AiAssistant
{
    /**
    * @return array{summary:string,suspicious:array<int,string>,filters:array<string,mixed>,raw:string}
     */
    public function analyzeCase(CaseFile $case, array $context, string $userPrompt = ''): array
    {
        if (!config('analytica.ai.enabled')) {
            return $this->buildLocalFallbackAnalysis($context, 'Assistant IA désactivé (ANALYTICA_AI_ENABLED=false).');
        }

        $apiKey = (string) env('OPENAI_API_KEY', '');
        if ($apiKey === '') {
            return $this->buildLocalFallbackAnalysis($context, 'OPENAI_API_KEY manquante.');
        }

        $baseUrl = rtrim((string) config('analytica.ai.openai_base_url'), '/');
        $model = (string) config('analytica.ai.openai_model');
        $timeout = (int) config('analytica.ai.timeout_seconds', 45);

        $system = <<<TXT
Tu es l'assistant d'analyse bancaire d'Analytica.
Objectif: produire une lecture d'expert, factuelle, neutre et argumentée du dossier (sans accusation), avec hypothèses vérifiables.
Tu dois rapprocher les bénéficiaires quand les libellés suggèrent la même entité (ex: variations orthographiques, nom marital/naissance, assureur et sinistre incendie/feu).
Proposer des filtres concrets (période, montants, catégories: transfer/cheque/cash_withdrawal/card).
Réponds STRICTEMENT en JSON valide avec les clés:
- summary: string
- suspicious: array of strings (points d'attention)
- filters: object (champs possibles: date_from,date_to,min_amount,max_amount,type,kind)
- raw: string (optionnel: notes)
Rédige en français clair, court et factuel.
Le champ summary doit être une synthèse experte et actionnable en 5 à 8 phrases, avec éléments quantifiés quand disponibles.
Quand pertinent, formule les points dans suspicious en commençant par des formulations de ce style:
- "Augmentation significative ..."
- "Concentration anormale ..."
- "Multiplication de virements fractionnés ..."
Ne fabrique pas de transactions.
TXT;

        $caseMeta = [
            'case' => [
                'id' => $case->getKey(),
                'title' => $case->title,
                'deceased_name' => $case->deceased_name,
                'death_date' => $case->death_date?->format('Y-m-d'),
                'analysis_period_start' => $case->analysis_period_start?->format('Y-m-d'),
                'analysis_period_end' => $case->analysis_period_end?->format('Y-m-d'),
                'status' => $case->status,
                'global_score' => $case->global_score,
            ],
            'context' => $context,
        ];

        $user = trim($userPrompt) !== ''
            ? "Demande utilisateur: {$userPrompt}"
            : "Donne un résumé et des filtres utiles pour explorer les transactions.";

        try {
            $resp = Http::baseUrl($baseUrl)
                ->withToken($apiKey)
                ->timeout($timeout)
                ->acceptJson()
                ->asJson()
                ->post('/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.2,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $user],
                        ['role' => 'user', 'content' => json_encode($caseMeta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
                    ],
                ]);
        } catch (ConnectionException $e) {
            return $this->buildLocalFallbackAnalysis($context, 'Impossible de joindre OpenAI (timeout/connexion).');
        }

        if (!$resp->successful()) {
            return $this->buildLocalFallbackAnalysis($context, 'Appel IA échoué: HTTP '.$resp->status());
        }

        $content = (string) ($resp->json('choices.0.message.content') ?? '');
        $decoded = json_decode($content, true);

        if (!is_array($decoded)) {
            return $this->buildLocalFallbackAnalysis($context, 'Réponse IA non interprétable.', $content);
        }

        return [
            'summary' => (string) ($decoded['summary'] ?? ''),
            'suspicious' => array_values(array_filter((array) ($decoded['suspicious'] ?? []), fn ($v) => is_string($v) && $v !== '')),
            'filters' => is_array($decoded['filters'] ?? null) ? $decoded['filters'] : [],
            'raw' => (string) ($decoded['raw'] ?? ''),
        ];
    }

    /**
     * @return array{summary:string,suspicious:array<int,string>,filters:array<string,mixed>,raw:string}
     */
    private function buildLocalFallbackAnalysis(array $context, string $reason, string $raw = ''): array
    {
        $transactions = collect((array) ($context['transactions'] ?? []))
            ->filter(fn ($row) => is_array($row))
            ->values();

        if ($transactions->isEmpty()) {
            return [
                'summary' => 'Aucune transaction exploitable pour produire un compte rendu automatique.',
                'suspicious' => [],
                'filters' => [],
                'raw' => trim($reason.' '.($raw !== '' ? $raw : '')),
            ];
        }

        $credits = (float) $transactions
            ->where('type', 'credit')
            ->sum(fn ($t) => abs((float) ($t['amount'] ?? 0)));
        $debits = (float) $transactions
            ->where('type', 'debit')
            ->sum(fn ($t) => abs((float) ($t['amount'] ?? 0)));
        $net = $credits - $debits;

        $highValueThreshold = 20000.0;
        $highValues = $transactions
            ->filter(fn ($t) => abs((float) ($t['amount'] ?? 0)) >= $highValueThreshold)
            ->values();

        $cashWithdrawals = $transactions
            ->filter(fn ($t) => (string) ($t['kind'] ?? '') === 'cash_withdrawal' && (string) ($t['type'] ?? '') === 'debit')
            ->values();

        $byMonthCash = $cashWithdrawals
            ->groupBy(fn ($t) => substr((string) ($t['date'] ?? ''), 0, 7))
            ->map(fn ($rows) => (float) collect($rows)->sum(fn ($t) => abs((float) ($t['amount'] ?? 0))));
        $cashAvg = $byMonthCash->count() > 0 ? (float) $byMonthCash->avg() : 0.0;
        $cashPeakAmount = $byMonthCash->count() > 0 ? (float) $byMonthCash->max() : 0.0;
        $cashPeakMonth = $byMonthCash->sortDesc()->keys()->first();

        $topDebit = $transactions
            ->where('type', 'debit')
            ->sortByDesc(fn ($t) => abs((float) ($t['amount'] ?? 0)))
            ->take(1)
            ->first();

        $monthly = $transactions
            ->groupBy(fn ($t) => substr((string) ($t['date'] ?? ''), 0, 7))
            ->map(function ($rows, $month) {
                $rows = collect($rows);

                return [
                    'month' => (string) $month,
                    'credits' => (float) $rows->where('type', 'credit')->sum(fn ($t) => abs((float) ($t['amount'] ?? 0))),
                    'debits' => (float) $rows->where('type', 'debit')->sum(fn ($t) => abs((float) ($t['amount'] ?? 0))),
                ];
            })
            ->values();

        $maxDebitMonth = collect($monthly)->sortByDesc('debits')->first();
        $maxCreditMonth = collect($monthly)->sortByDesc('credits')->first();

        $beneficiaryGroups = $transactions
            ->where('type', 'debit')
            ->groupBy(function ($t) {
                $candidate = trim((string) ($t['destination'] ?? $t['origin'] ?? $t['normalized_label'] ?? $t['label'] ?? 'INCONNU'));
                $normalized = Normalization::normalizeLabel($candidate);
                $strictIdentity = $this->resolveStrictGiordanoIdentity($normalized);
                if ($strictIdentity !== null) {
                    return (string) ($strictIdentity['key'] ?? 'INCONNU');
                }
                $tokens = preg_split('/\s+/u', $normalized) ?: [];
                $tokens = array_values(array_filter($tokens, fn ($token) => is_string($token) && $token !== ''));

                $cluster = $this->matchBeneficiaryAliasCluster($normalized, $tokens);

                return $cluster['key'] ?? ($normalized !== '' ? $normalized : 'INCONNU');
            })
            ->map(fn ($rows) => (float) collect($rows)->sum(fn ($t) => abs((float) ($t['amount'] ?? 0))))
            ->sortDesc();

        $topBenefKey = (string) ($beneficiaryGroups->keys()->first() ?? '');
        $topBenefAmount = (float) ($beneficiaryGroups->first() ?? 0);
        $topBenefShare = $debits > 0 ? round(($topBenefAmount / $debits) * 100, 1) : 0.0;

        $topBenefCluster = $this->findAliasClusterByKey($topBenefKey);
        $strictTopBenefIdentity = $this->strictGiordanoLabelByKey($topBenefKey);
        $topBenefLabel = $strictTopBenefIdentity ?? ($topBenefCluster['label'] ?? ($topBenefKey === 'INCONNU' ? 'Inconnu' : mb_substr($topBenefKey, 0, 90)));

        $summaryParts = [
            'Conclusion d\'analyse bancaire (mode local objectif, sans appel OpenAI).',
            sprintf('Sur les %d opérations analysées, les flux totaux sont de %.2f € en crédits et %.2f € en débits, soit un solde net de %.2f €.', $transactions->count(), $credits, $debits, $net),
        ];

        if ($highValues->count() > 0) {
            $summaryParts[] = sprintf('Les montants unitaires élevés (>= %.0f €) représentent %d opération(s), ce qui justifie un contrôle prioritaire des justificatifs associés.', $highValueThreshold, $highValues->count());
        }

        if ($cashPeakMonth) {
            $summaryParts[] = sprintf('Les retraits espèces montrent une moyenne mensuelle de %.2f € avec un pic à %.2f € en %s, à recontextualiser avec les événements du dossier.', $cashAvg, $cashPeakAmount, $cashPeakMonth);
        }

        if (is_array($maxDebitMonth)) {
            $summaryParts[] = sprintf('Le mois le plus chargé en débits est %s (%.2f €), indiquant une concentration temporelle des sorties.', (string) ($maxDebitMonth['month'] ?? '—'), (float) ($maxDebitMonth['debits'] ?? 0));
        }

        if (is_array($maxCreditMonth)) {
            $summaryParts[] = sprintf('Le mois le plus chargé en crédits est %s (%.2f €).', (string) ($maxCreditMonth['month'] ?? '—'), (float) ($maxCreditMonth['credits'] ?? 0));
        }

        if ($topBenefAmount > 0) {
            $summaryParts[] = sprintf('Le principal pôle de sortie est "%s" avec %.2f € (%.1f %% des débits), ce qui oriente l\'analyse sur cette relation financière.', $topBenefLabel, $topBenefAmount, $topBenefShare);
        }

        $suspicious = [];
        if ($highValues->count() > 0) {
            $suspicious[] = sprintf('Augmentation significative des montants unitaires: %d mouvement(s) >= %.0f €.', $highValues->count(), $highValueThreshold);
        }
        if ($cashPeakMonth && $cashAvg > 0 && $cashPeakAmount >= ($cashAvg * 1.8)) {
            $suspicious[] = sprintf('Retraits espèces atypiques: pic mensuel de %.2f € en %s, supérieur à la moyenne mensuelle.', $cashPeakAmount, $cashPeakMonth);
        }
        if (is_array($topDebit)) {
            $label = trim((string) ($topDebit['normalized_label'] ?? $topDebit['label'] ?? 'libellé indisponible'));
            $suspicious[] = sprintf('Concentration ponctuelle sur un débit élevé: %.2f € (%s).', abs((float) ($topDebit['amount'] ?? 0)), mb_substr($label, 0, 120));
        }
        if ($topBenefAmount > 0 && $topBenefShare >= 25) {
            $suspicious[] = sprintf('Concentration anormale des sorties sur un bénéficiaire rapproché: %s (%.1f %% des débits).', $topBenefLabel, $topBenefShare);
        }
        if ($strictTopBenefIdentity !== null) {
            $suspicious[] = sprintf('Rapprochement nominal strict appliqué: entité identifiée comme "%s".', $strictTopBenefIdentity);
        } elseif (!empty($topBenefCluster)) {
            $suspicious[] = sprintf('Rapprochement de variantes de libellés sur la même entité: %s.', (string) ($topBenefCluster['label'] ?? $topBenefKey));
        }

        $filters = [];
        if ($highValues->count() > 0) {
            $filters['min_amount'] = 20000;
        }
        if ($cashPeakMonth) {
            $filters['kind'] = 'cash_withdrawal';
            $filters['date_from'] = $cashPeakMonth.'-01';
            $filters['date_to'] = $cashPeakMonth.'-31';
        }
        if ($topBenefKey === 'PERSONNE_LILIANE_GIORDANO_NOVAK') {
            $filters['q'] = 'MME GIORDANO NOVAK LILIANE';
        } elseif ($topBenefKey === 'PERSONNE_M_GIORDANO') {
            $filters['q'] = 'MR GIORDANO MONSIEUR GIORDANO';
        } elseif ($topBenefKey === 'COMPTE_COMMUN_GIORDANO') {
            $filters['q'] = 'M OU MME GIORDANO MME MR GIORDANO';
        } elseif ($topBenefKey === 'PERSONNE_ANTHONY_GIORDANO') {
            $filters['q'] = 'ANTHONY GIORDANO';
        } elseif ($topBenefKey === 'PERSONNE_EMILIE_GIORDANO') {
            $filters['q'] = 'EMILIE GIORDANO';
        } elseif (!empty($topBenefCluster['query'] ?? '')) {
            $filters['q'] = (string) $topBenefCluster['query'];
        }

        return [
            'summary' => implode(' ', $summaryParts),
            'suspicious' => array_values(array_unique($suspicious)),
            'filters' => $filters,
            'raw' => trim($reason.' '.($raw !== '' ? $raw : '')),
        ];
    }

    /**
     * @param array<int,string> $tokens
     * @return array{key:string,label:string,query?:string}|null
     */
    private function matchBeneficiaryAliasCluster(string $normalized, array $tokens): ?array
    {
        $clusters = $this->beneficiaryAliasClusters();

        foreach ($clusters as $cluster) {
            $clusterTokens = collect((array) ($cluster['tokens'] ?? []))
                ->map(fn ($value) => Normalization::normalizeLabel((string) $value))
                ->filter(fn ($value) => $value !== '')
                ->unique()
                ->values()
                ->all();

            if ($clusterTokens === []) {
                continue;
            }

            $matches = 0;
            foreach ($clusterTokens as $clusterToken) {
                if (in_array($clusterToken, $tokens, true) || str_contains($normalized, $clusterToken)) {
                    $matches++;
                }
            }

            $minMatch = max(1, (int) ($cluster['min_match'] ?? 1));
            if ($matches < $minMatch) {
                continue;
            }

            return [
                'key' => (string) ($cluster['key'] ?? ''),
                'label' => (string) ($cluster['label'] ?? ''),
                'query' => (string) ($cluster['query'] ?? ''),
            ];
        }

        return null;
    }

    /**
     * @return array<int,array{key:string,label:string,tokens?:array<int,string>,min_match?:int,query?:string}>
     */
    private function beneficiaryAliasClusters(): array
    {
        $clusters = config('analytica.beneficiary_alias_clusters', []);

        return is_array($clusters) ? $clusters : [];
    }

    /**
     * @return array{key:string,label:string,tokens?:array<int,string>,min_match?:int,query?:string}|null
     */
    private function findAliasClusterByKey(string $key): ?array
    {
        if ($key === '') {
            return null;
        }

        /** @var Collection<int,array{key:string,label:string,tokens?:array<int,string>,min_match?:int,query?:string}> $clusters */
        $clusters = collect($this->beneficiaryAliasClusters());

        $cluster = $clusters->first(fn ($row) => (string) ($row['key'] ?? '') === $key);

        return is_array($cluster) ? $cluster : null;
    }

    /**
     * @return array{key:string,label:string}|null
     */
    private function resolveStrictGiordanoIdentity(string $normalized): ?array
    {
        if ($normalized === '') {
            return null;
        }

        $hasGiordano = preg_match('/\bGI?ORDANO\b/u', $normalized) === 1;
        $hasNovak = preg_match('/\bNOVAK\b/u', $normalized) === 1;
        $hasLiliane = preg_match('/\bLILIANE\b/u', $normalized) === 1;
        $hasAnthonyNamed = preg_match('/\b(?:GI?ORDANO\s+ANTHONY|ANTHONY\s+GI?ORDANO)\b/u', $normalized) === 1
            || ($hasGiordano && preg_match('/\bANTHONY\b/u', $normalized) === 1
                && preg_match('/\b(?:MME|MADAME|LILIANE|NOVAK)\b/u', $normalized) === 0);
        $hasEmilieNamed = preg_match('/\b(?:GI?ORDANO\s+EMILIE|EMILIE\s+GI?ORDANO|GI?ORDANO\s+EMILE|EMILE\s+GI?ORDANO)\b/u', $normalized) === 1;
        $hasChristianVariant =
            preg_match('/\b(?:CHRISTIAN|CHRISTAN|CHRESTIAN|CHRESTAN|CHRSTIAN|CHRSTAN)\b/u', $normalized) === 1
            || preg_match('/\bCHR[:\'"\s]*ST[:\'"\s]*AN\b/u', $normalized) === 1
            || preg_match('/\bCHR(?:I|E)?ST(?:I|E)?AN\b/u', $normalized) === 1
            || preg_match('/\bCHR[:\'"\s]*STE?AN\b/u', $normalized) === 1
            || preg_match('/\bCHRIST\b/u', $normalized) === 1
            || preg_match('/\bCHRSTE?\b/u', $normalized) === 1
            || preg_match('/\bCRYST\b/u', $normalized) === 1
            || preg_match('/\bCHR[A-Z]{0,2}ST[A-Z]{0,3}\b/u', $normalized) === 1;
        $hasFemaleTitle = preg_match('/\b(MME|MADAME)\b/u', $normalized) === 1;
        $hasMaleTitle = preg_match('/\b(MR|MONSIEUR)\b/u', $normalized) === 1;

        $hasJointMarker =
            preg_match('/\bM\s*OU\s*MME\b/u', $normalized) === 1
            || preg_match('/\bM\s*ET\s*MME\b/u', $normalized) === 1
            || preg_match('/\bM\b.{0,24}\bO[UÙ]\b.{0,12}\bMME\b/u', $normalized) === 1
            || preg_match('/\bMME\b.{0,24}\bO[UÙ]\b.{0,12}\bM\b/u', $normalized) === 1
            || preg_match('/\b(MR|MONSIEUR)\b.*\b(MME|MADAME)\b/u', $normalized) === 1
            || preg_match('/\b(MME|MADAME)\b.*\b(MR|MONSIEUR)\b/u', $normalized) === 1
            || ($hasChristianVariant && $hasFemaleTitle && $hasGiordano);

        $hasFemaleBeneficiaryContext =
            preg_match('/\b(?:BEN|IBEN|BENEF|BENEFICIAIRE|DEST|DESTINATAIRE|VERS)\b.{0,90}\b(?:MME|MADAME|LILIANE|NOVAK)\b/u', $normalized) === 1
            || preg_match('/\b(?:MME|MADAME|LILIANE|NOVAK)\b.{0,90}\b(?:BEN|IBEN|BENEF|BENEFICIAIRE|DEST|DESTINATAIRE|VERS)\b/u', $normalized) === 1;

        if ($hasAnthonyNamed) {
            return ['key' => 'PERSONNE_ANTHONY_GIORDANO', 'label' => 'M. Anthony GIORDANO'];
        }

        if ($hasEmilieNamed) {
            return ['key' => 'PERSONNE_EMILIE_GIORDANO', 'label' => 'Mme Emilie GIORDANO'];
        }

        if ($hasJointMarker && $hasGiordano) {
            return ['key' => 'COMPTE_COMMUN_GIORDANO', 'label' => 'M. ou Mme GIORDANO (compte commun)'];
        }

        if ($hasChristianVariant && ($hasNovak || $hasLiliane || ($hasFemaleTitle && $hasGiordano)) && $hasFemaleBeneficiaryContext) {
            return ['key' => 'PERSONNE_LILIANE_GIORDANO_NOVAK', 'label' => 'Mme Liliane GIORDANO / NOVAK'];
        }

        if (($hasMaleTitle && $hasGiordano) || ($hasChristianVariant && $hasGiordano)) {
            return ['key' => 'PERSONNE_M_GIORDANO', 'label' => 'M. GIORDANO'];
        }

        if (($hasFemaleTitle && ($hasGiordano || $hasNovak)) || $hasLiliane || $hasNovak) {
            return ['key' => 'PERSONNE_LILIANE_GIORDANO_NOVAK', 'label' => 'Mme Liliane GIORDANO / NOVAK'];
        }

        return null;
    }

    private function strictGiordanoLabelByKey(string $key): ?string
    {
        return match ($key) {
            'PERSONNE_LILIANE_GIORDANO_NOVAK' => 'Mme Liliane GIORDANO / NOVAK',
            'PERSONNE_M_GIORDANO' => 'M. GIORDANO',
            'COMPTE_COMMUN_GIORDANO' => 'M. ou Mme GIORDANO (compte commun)',
            'PERSONNE_ANTHONY_GIORDANO' => 'M. Anthony GIORDANO',
            'PERSONNE_EMILIE_GIORDANO' => 'Mme Emilie GIORDANO',
            default => null,
        };
    }
}
