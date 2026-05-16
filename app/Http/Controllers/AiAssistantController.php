<?php

namespace App\Http\Controllers;

use App\Models\CaseFile;
use App\Services\AiAssistant;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AiAssistantController extends Controller
{
    public function analyze(Request $request, CaseFile $case, AiAssistant $assistant)
    {
        Gate::authorize('view', $case);

        $validated = $request->validate([
            'prompt' => ['nullable', 'string', 'max:1000'],
        ]);

        $case->load(['bankAccounts.statements']);
        $accountIds = $case->bankAccounts()->pluck('id');

        // Limit transactions only when sending to OpenAI (token budget).
        // When AI is disabled the summary is computed locally—use all transactions.
        $aiActive = (bool) config('analytica.ai.enabled') && env('OPENAI_API_KEY', '') !== '';
        $max = $aiActive ? (int) config('analytica.ai.max_transactions', 300) : null;

        $txQuery = Transaction::query()
            ->whereIn('bank_account_id', $accountIds)
            ->orderByDesc('date')
            ->orderByDesc('id');
        if ($max !== null) {
            $txQuery->limit($max);
        }

        $transactions = $txQuery->get([
                'date', 'amount', 'type', 'kind', 'origin', 'destination', 'motif', 'cheque_number', 'label', 'normalized_label', 'anomaly_score',
            ])
            ->map(function ($t) {
                return [
                    'date' => optional($t->date)->format('Y-m-d'),
                    'amount' => (float) $t->amount,
                    'type' => (string) $t->type,
                    'kind' => $t->kind,
                    'origin' => $t->origin,
                    'destination' => $t->destination,
                    'motif' => $t->motif,
                    'cheque_number' => $t->cheque_number,
                    'label' => $t->label,
                    'normalized_label' => $t->normalized_label,
                    'anomaly_score' => $t->anomaly_score,
                ];
            })
            ->values()
            ->all();

        $statements = $case->bankAccounts
            ->flatMap(fn ($a) => $a->statements)
            ->sortByDesc('created_at')
            ->take(10)
            ->map(fn ($s) => [
                'filename' => $s->original_filename,
                'status' => $s->import_status,
                'transactions_imported' => $s->transactions_imported,
                'ocr_used' => (bool) $s->ocr_used,
                'message' => $s->import_error,
                'created_at' => optional($s->created_at)->toIso8601String(),
            ])
            ->values()
            ->all();

        $context = [
            'transactions_count' => count($transactions),
            'transactions' => $transactions,
            'recent_statements' => $statements,
        ];

        try {
            $result = $assistant->analyzeCase($case, $context, (string) ($validated['prompt'] ?? ''));
        } catch (\Throwable $e) {
            $case->forceFill([
                'ai_last_prompt' => (string) ($validated['prompt'] ?? ''),
                'ai_last_error' => $e->getMessage(),
                'ai_last_ran_at' => now(),
            ])->save();

            $request->session()->put('cases.'.$case->getKey().'.last_ai', [
                'prompt' => (string) ($validated['prompt'] ?? ''),
                'error' => $e->getMessage(),
                'ran_at' => now()->toIso8601String(),
            ]);

            return redirect()
                ->route('cases.show', $case)
                ->with('ai_error', $e->getMessage());
        }

        $case->forceFill([
            'ai_last_prompt' => (string) ($validated['prompt'] ?? ''),
            'ai_last_result' => $result,
            'ai_last_error' => null,
            'ai_last_ran_at' => now(),
        ])->save();

        $request->session()->put('cases.'.$case->getKey().'.last_ai', [
            'prompt' => (string) ($validated['prompt'] ?? ''),
            'result' => $result,
            'ran_at' => now()->toIso8601String(),
        ]);

        return redirect()
            ->route('cases.show', $case)
            ->with('ai_result', $result);
    }

    public function semanticSearch(Request $request, CaseFile $case): \Illuminate\Http\JsonResponse
    {
        Gate::authorize('view', $case);

        $validated = $request->validate([
            'theme' => ['required', 'string', 'max:200'],
        ]);

        $theme = trim($validated['theme']);

        $apiKey = (string) env('OPENAI_API_KEY', '');
        if ($apiKey === '' || !config('analytica.ai.enabled')) {
            return response()->json(['keywords' => [$theme]]);
        }

        $baseUrl = rtrim((string) config('analytica.ai.openai_base_url'), '/');
        $model = (string) config('analytica.ai.openai_model');

        try {
            $resp = \Illuminate\Support\Facades\Http::baseUrl($baseUrl)
                ->withToken($apiKey)
                ->timeout(15)
                ->acceptJson()
                ->asJson()
                ->post('/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.3,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un expert en libellés de relevés bancaires français. Réponds UNIQUEMENT en JSON valide: {"keywords": ["MOT1", "MOT2", ...]}. Donne 6 à 10 mots-clés courts (1-3 mots) en MAJUSCULES tels qu\'ils apparaissent typiquement dans des libellés bancaires français pour le thème demandé. Inclus variantes orthographiques courantes et abréviations OCR.',
                        ],
                        [
                            'role' => 'user',
                            'content' => "Thème: {$theme}\nMots-clés bancaires correspondants?",
                        ],
                    ],
                ]);

            if ($resp->successful()) {
                $decoded = json_decode((string) ($resp->json('choices.0.message.content') ?? ''), true);
                $keywords = array_values(array_filter(
                    (array) ($decoded['keywords'] ?? []),
                    fn ($v) => is_string($v) && trim($v) !== ''
                ));
                if (!empty($keywords)) {
                    return response()->json(['keywords' => array_slice($keywords, 0, 10)]);
                }
            }
        } catch (\Throwable) {
            // fall through
        }

        return response()->json(['keywords' => [$theme]]);
    }
}
