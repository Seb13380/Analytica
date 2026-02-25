<?php

namespace App\Services;

use App\Models\CaseFile;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class AiAssistant
{
    /**
     * @return array{summary:string,suspicious:array<int,string>,filters:array<string,mixed>,raw:string}
     */
    public function analyzeCase(CaseFile $case, array $context, string $userPrompt = ''): array
    {
        if (!config('analytica.ai.enabled')) {
            throw new \RuntimeException('Assistant IA désactivé (ANALYTICA_AI_ENABLED=false).');
        }

        $apiKey = (string) env('OPENAI_API_KEY', '');
        if ($apiKey === '') {
            throw new \RuntimeException('OPENAI_API_KEY manquante.');
        }

        $baseUrl = rtrim((string) config('analytica.ai.openai_base_url'), '/');
        $model = (string) config('analytica.ai.openai_model');
        $timeout = (int) config('analytica.ai.timeout_seconds', 45);

        $system = <<<TXT
Tu es l'assistant d'analyse bancaire d'Analytica.
Objectif: aider à comprendre le dossier, suggérer des anomalies potentielles, et proposer des filtres concrets (période, montants, catégories: transfer/cheque/cash_withdrawal/other).
Réponds STRICTEMENT en JSON valide avec les clés:
- summary: string
- suspicious: array of strings (points d'attention)
- filters: object (champs possibles: date_from,date_to,min_amount,max_amount,type,kind)
- raw: string (optionnel: notes)
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
            throw new \RuntimeException('Impossible de joindre OpenAI (timeout/connexion).');
        }

        if (!$resp->successful()) {
            throw new \RuntimeException('Appel IA échoué: HTTP '.$resp->status());
        }

        $content = (string) ($resp->json('choices.0.message.content') ?? '');
        $decoded = json_decode($content, true);

        if (!is_array($decoded)) {
            return [
                'summary' => '',
                'suspicious' => [],
                'filters' => [],
                'raw' => $content,
            ];
        }

        return [
            'summary' => (string) ($decoded['summary'] ?? ''),
            'suspicious' => array_values(array_filter((array) ($decoded['suspicious'] ?? []), fn ($v) => is_string($v) && $v !== '')),
            'filters' => is_array($decoded['filters'] ?? null) ? $decoded['filters'] : [],
            'raw' => (string) ($decoded['raw'] ?? ''),
        ];
    }
}
