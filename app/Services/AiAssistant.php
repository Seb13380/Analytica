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
Tu es un expert en analyse forensique bancaire au service d'un notaire ou d'un avocat.
Ton rôle: analyser les flux financiers d'un dossier successoral ou judiciaire pour détecter des anomalies, des comportements suspects ou des incohérences, et formuler des hypothèses vérifiables.

MÉTHODE D'ANALYSE:
1. Identifier les flux inhabituels (montants, fréquences, bénéficiaires) par rapport au profil global
2. Rapprocher les contreparties similaires (variantes orthographiques OCR, noms mariés/naissance)
3. Analyser la chronologie: séquences significatives (gros crédit suivi de retraits, virements répétés fractionnés)
4. Distinguer les flux réguliers (salaires, retraites, loyers) des flux exceptionnels
5. Signaler les manques de justificatifs probables pour les gros flux

RÈGLES DE RAISONNEMENT:
- Un virement reçu d'une notaire suivi d'un remboursement 7j après = vente immobilière + plus-value (normal)
- Des virements fractionnés répétés vers un même bénéficiaire = possible contournement seuil
- Un gros retrait espèces sans justificatif = point d'attention UNIQUEMENT s'il a lieu juste avant ou juste après la date de décès (fenêtre ±60 jours). Hors de cette période, les retraits espèces ne nécessitent PAS de justificatif.
- Des virements internes entre comptes du même titulaire ≠ sorties patrimoniales
- "TRAVAUX" + montants > 10 000€ sans devis/facture = vérification recommandée
- Des crédits récurrents de même montant = revenu régulier probable (pension, loyer)
- Une contrepartie qui reçoit beaucoup sans jamais verser = bénéficiaire unilatéral (à justifier)
- Maître MILLET (ou MILLET notaire) : un unique virement correspond au remboursement de plus-value immobilière — opération normale, ne pas demander de justificatif supplémentaire
- Chèques (kind=cheque) : ne demander de justificatif QUE si le montant unitaire est >= 7 000€. En dessous de 7 000€, pas de justificatif demandé.
- Virements (kind=transfer ou virement) : ne signaler comme suspect et demander justificatif QUE si le montant est >= 5 000€. Un virement de 1 000€ ou moins ne nécessite aucune mention.
- Paiements par carte (kind=card) : ne pas signaler comme "paiements par carte élevés" sauf si la transaction n'est pas un virement mal catégorisé — vérifier le libellé. Un libellé "VIR" ou "VIREMENT" avec kind=card = erreur de catégorie, traiter comme virement.

Réponds STRICTEMENT en JSON valide avec les clés:
- summary: array of 3 strings (tableau JSON de 3 chaînes). Élément 0: profil global — période analysée, total crédits, total débits, épargne nette, revenus réguliers identifiés (2-3 phrases). Élément 1: événements financiers majeurs chronologiques — nommer chaque contrepartie importante avec montant exact (2-3 phrases). Élément 2: conclusion — uniquement les points nécessitant des justificatifs avec dates et montants (2-3 phrases). PAS de commentaire hors du tableau.
- suspicious: array of strings (points d'attention précis avec montants et dates)
- filters: object (champs: date_from, date_to, min_amount, max_amount, type, kind)
- raw: string (optionnel: observations complémentaires)

Le tableau "summary" doit contenir EXACTEMENT 3 éléments:
- summary[0]: profil global (revenus, dépenses, épargne nette, période, revenus réguliers)
- summary[1]: événements financiers majeurs chronologiques (nommer contreparties avec montants)
- summary[2]: conclusion — uniquement les points nécessitant des justificatifs

Le champ "suspicious" doit contenir des formulations précises comme:
- "Virement de 135 512€ vers NOVAK (2021-05-04) sans justificatif apparent — demander facture travaux"
- "Série de 16 virements vers Liliane GIORDANO/NOVAK (total 161 439€) — flux unilatéral sortant"
- "Chèque de X€ le Y — demander justificatif" (UNIQUEMENT si X >= 7 000€)
- "Retrait espèces de X€ le Y — contexte sensible (proche du décès)" (UNIQUEMENT si dans les 60 jours avant/après le décès)
- NE PAS mentionner les retraits espèces hors période sensible
- NE PAS demander de justificatif pour les virements < 5 000€
- NE PAS mentionner "paiements par carte" si le libellé contient "VIR" ou "VIREMENT" (erreur de catégorie OCR)
Ne fabrique pas de transactions. Reste factuel et neutre.
Rédige en français professionnel.

ATTENTION CRITIQUE:
- Le champ flux_resume.exceptional_ops contient TOUTES les opérations >= 20 000€ avec leur kind exact (cheque, virement, cash_withdrawal, etc.).
- Le champ flux_resume.by_kind_summary donne pour chaque kind: count, total, max_single.
- Tu DOIS utiliser ces champs pour rapporter les montants exacts. Ne déduis jamais les montants depuis d'autres sources.
- Si by_kind_summary contient kind="cheque" avec max_single=30000, tu dois écrire "chèques jusqu'à 30 000€", pas 1 970€.
- Cite le montant max réel de chaque catégorie depuis by_kind_summary.max_single.
- VIREMENT MAÎTRE MILLET: un seul virement vers Maître Millet = remboursement de plus-value immobilière. Opération normale, ne pas émettre d'alerte.
- CHÈQUES: ne demander de justificatif que pour les chèques >= 7 000€. Les chèques inférieurs à 7 000€ ne nécessitent aucune mention.
- RETRAITS ESPÈCES: ne signaler que les retraits dans les 60 jours avant/après la date de décès du dossier. Les autres retraits sont normaux.
- VIREMENTS: seuil de signalement = 5 000€ minimum. Ignorer les virements < 5 000€.
- KIND=CARD + LIBELLÉ "VIR": si exceptional_ops contient un flux kind=card mais dont le label contient "VIR" ou "VIREMENT", traiter comme virement et ne pas écrire "paiement par carte".
TXT;

        $caseMeta = [
            'case' => [
                'id'                     => $case->getKey(),
                'title'                  => $case->title,
                'deceased_name'          => $case->deceased_name,
                'death_date'             => $case->death_date?->format('Y-m-d'),
                'analysis_period_start'  => $case->analysis_period_start?->format('Y-m-d'),
                'analysis_period_end'    => $case->analysis_period_end?->format('Y-m-d'),
                'status'                 => $case->status,
                'global_score'           => $case->global_score,
            ],
            'flux_resume' => $this->buildFluxResume($context),
            'context'     => $context,
        ];

        $user = trim($userPrompt) !== ''
            ? "Demande utilisateur: {$userPrompt}\n\nAnalyse le dossier en t'appuyant sur les données flux_resume et context fournis."
            : "Réalise une analyse forensique complète du dossier. Utilise les données flux_resume pour nommer précisément les contreparties et leurs montants.";

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

        // summary can be an array of paragraphs (preferred) or a plain string (fallback)
        $rawSummary = $decoded['summary'] ?? '';
        if (is_array($rawSummary)) {
            $summary = implode("\n\n", array_map('trim', $rawSummary));
        } else {
            $summary = (string) $rawSummary;
        }

        return [
            'summary' => $summary,
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

        $para1Parts = [
            sprintf('Sur les %d opérations analysées, les flux totaux sont de %.2f € en crédits et %.2f € en débits, soit un solde net de %.2f €.', $transactions->count(), $credits, $debits, $net),
        ];

        $para2Parts = [];

        if ($highValues->count() > 0) {
            $para2Parts[] = sprintf('Les montants unitaires élevés (>= %.0f €) représentent %d opération(s), ce qui justifie un contrôle prioritaire des justificatifs associés.', $highValueThreshold, $highValues->count());
        }

        if ($cashPeakMonth) {
            $para2Parts[] = sprintf('Les retraits espèces montrent une moyenne mensuelle de %.2f € avec un pic à %.2f € en %s, à recontextualiser avec les événements du dossier.', $cashAvg, $cashPeakAmount, $cashPeakMonth);
        }

        if (is_array($maxDebitMonth)) {
            $para2Parts[] = sprintf('Le mois le plus chargé en débits est %s (%.2f €), indiquant une concentration temporelle des sorties.', (string) ($maxDebitMonth['month'] ?? '—'), (float) ($maxDebitMonth['debits'] ?? 0));
        }

        if (is_array($maxCreditMonth)) {
            $para2Parts[] = sprintf('Le mois le plus chargé en crédits est %s (%.2f €).', (string) ($maxCreditMonth['month'] ?? '—'), (float) ($maxCreditMonth['credits'] ?? 0));
        }

        $para3Parts = [];

        if ($topBenefAmount > 0) {
            $para3Parts[] = sprintf('Le principal pôle de sortie est "%s" avec %.2f € (%.1f %% des débits), ce qui oriente l\'analyse sur cette relation financière.', $topBenefLabel, $topBenefAmount, $topBenefShare);
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
            'summary' => implode("\n\n", array_filter([
                implode(' ', $para1Parts),
                implode(' ', $para2Parts),
                implode(' ', $para3Parts),
            ])),
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

    /**
     * Construit un résumé structuré des flux par contrepartie + totaux globaux
     * pour enrichir le contexte envoyé au LLM.
     *
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    private function buildFluxResume(array $context): array
    {
        $transactions = collect((array) ($context['transactions'] ?? []))->filter(fn ($r) => is_array($r));

        $totalCredits  = $transactions->where('type', 'credit')->sum(fn ($t) => abs((float) ($t['amount'] ?? 0)));
        $totalDebits   = $transactions->where('type', 'debit')->sum(fn ($t) => abs((float) ($t['amount'] ?? 0)));
        $cashOuts      = $transactions->filter(fn ($t) => ($t['kind'] ?? '') === 'cash_withdrawal' && ($t['type'] ?? '') === 'debit');
        $highValueOps  = $transactions->filter(fn ($t) => abs((float) ($t['amount'] ?? 0)) >= 20000)->sortByDesc(fn ($t) => abs((float) ($t['amount'] ?? 0)))->take(20);

        // Flux par bénéficiaire (via destination/origin déjà enrichis si disponibles)
        $byBenef = $transactions->groupBy(function ($t) {
            // Priorité: champ beneficiary_detected groupé, sinon destination/origin brut
            if (!empty($t['beneficiary_label'])) {
                return (string) $t['beneficiary_label'];
            }
            if (($t['type'] ?? '') === 'debit' && !empty($t['destination'])) {
                return (string) $t['destination'];
            }
            if (($t['type'] ?? '') === 'credit' && !empty($t['origin'])) {
                return (string) $t['origin'];
            }
            return 'Autre/inconnu';
        });

        $counterparties = $byBenef->map(function ($rows, $label) {
            $rows = collect($rows);
            $credits = $rows->where('type', 'credit')->sum(fn ($t) => abs((float) ($t['amount'] ?? 0)));
            $debits  = $rows->where('type', 'debit')->sum(fn ($t) => abs((float) ($t['amount'] ?? 0)));
            return [
                'label'    => $label,
                'credits'  => round($credits, 2),
                'debits'   => round($debits, 2),
                'nb_mvts'  => $rows->count(),
                'net_out'  => round($debits - $credits, 2),
            ];
        })->sortByDesc('net_out')->take(20)->values()->all();

        // Top opérations exceptionnelles (avec kind pour que le LLM distingue chèque/virement/espèces)
        $exceptional = $highValueOps->map(fn ($t) => [
            'date'           => $t['date'] ?? '',
            'amount'         => $t['amount'] ?? 0,
            'type'           => $t['type'] ?? '',
            'kind'           => $t['kind'] ?? null,
            'cheque_number'  => $t['cheque_number'] ?? null,
            'label'          => mb_substr((string) ($t['normalized_label'] ?? $t['label'] ?? ''), 0, 120),
        ])->values()->all();

        // Résumé par kind : max montant et total pour chaque catégorie
        $byKind = $transactions->groupBy(fn ($t) => ($t['kind'] ?? 'inconnu') ?: 'inconnu')
            ->map(fn ($rows) => [
                'count'      => collect($rows)->count(),
                'total'      => round(collect($rows)->sum(fn ($t) => abs((float) ($t['amount'] ?? 0))), 2),
                'max_single' => round(collect($rows)->max(fn ($t) => abs((float) ($t['amount'] ?? 0))), 2),
            ])->all();

        // Flux espèces mensuels
        $cashByMonth = $cashOuts->groupBy(fn ($t) => substr((string) ($t['date'] ?? ''), 0, 7))
            ->map(fn ($rows) => round(collect($rows)->sum(fn ($t) => abs((float) ($t['amount'] ?? 0))), 2))
            ->sortKeys()->all();

        return [
            'total_credits_eur'   => round($totalCredits, 2),
            'total_debits_eur'    => round($totalDebits, 2),
            'net_eur'             => round($totalCredits - $totalDebits, 2),
            'nb_transactions'     => $transactions->count(),
            'cash_total_eur'      => round($cashOuts->sum(fn ($t) => abs((float) ($t['amount'] ?? 0))), 2),
            'cash_by_month'       => $cashByMonth,
            'top_counterparties'  => $counterparties,
            'exceptional_ops'     => $exceptional,
            'by_kind_summary'     => $byKind,
        ];
    }
}
