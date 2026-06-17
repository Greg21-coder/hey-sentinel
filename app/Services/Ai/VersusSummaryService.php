<?php

namespace App\Services\Ai;

use App\Exceptions\Ai\VersusSummaryFailed;
use App\Models\AppComparisonSummary;
use App\Models\ShopifyApp;
use App\Support\Ai\VersusSummaryPrompt;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VersusSummaryService
{
    public function generate(int $accountId, ShopifyApp $mine, Collection $competitors, array $payload): AppComparisonSummary
    {
        $response = $this->http()->post('/api/chat', [
            'model' => $this->model(),
            'stream' => false,
            'format' => 'json',
            'options' => [
                'temperature' => (float) config('ai.ollama.temperature', 0.2),
                'num_predict' => 700,
            ],
            'messages' => [
                ['role' => 'system', 'content' => VersusSummaryPrompt::system()],
                ['role' => 'user', 'content' => VersusSummaryPrompt::userMessage($payload)],
            ],
        ]);

        if ($response->failed()) {
            throw new VersusSummaryFailed("VersusSummaryService HTTP {$response->status()}: ".$response->body());
        }

        $raw = (string) $response->json('message.content');
        $decoded = $this->decode($raw);
        if ($decoded === null) {
            throw new VersusSummaryFailed("VersusSummaryService invalid JSON: {$raw}");
        }

        $validIds = collect([$mine->id])->concat($competitors->pluck('id'))->all();
        $winner = isset($decoded['winner_app_id']) ? (int) $decoded['winner_app_id'] : null;
        if ($winner !== null && ! in_array($winner, $validIds, true)) {
            Log::channel('ai')->warning('versus.summary_winner_out_of_set', [
                'returned' => $winner,
                'valid' => $validIds,
            ]);
            $winner = null;
        }

        return AppComparisonSummary::updateOrCreate(
            [
                'account_id' => $accountId,
                'mine_shopify_app_id' => $mine->id,
                'competitor_ids_hash' => AppComparisonSummary::hashFor($competitors->pluck('id')->all()),
            ],
            [
                'competitor_ids' => $competitors->pluck('id')->all(),
                'summary' => (string) ($decoded['winner_reasoning'] ?? ''),
                'winner_shopify_app_id' => $winner,
                'winner_reasoning' => (string) ($decoded['winner_reasoning'] ?? ''),
                'per_metric_comments' => $decoded['per_metric_comments'] ?? [],
                'model' => $this->model(),
                'prompt_version' => VersusSummaryPrompt::VERSION,
                'generated_at' => now(),
            ]
        );
    }

    protected function decode(string $raw): ?array
    {
        $parsed = json_decode($raw, true);
        if (is_array($parsed)) {
            return $parsed;
        }
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $raw, $m)) {
            $parsed = json_decode($m[1], true);
            if (is_array($parsed)) {
                return $parsed;
            }
        }

        return null;
    }

    protected function http(): PendingRequest
    {
        return Http::baseUrl((string) config('ai.ollama.base_url'))
            ->timeout((int) config('ai.ollama.request_timeout', 180))
            ->acceptJson();
    }

    protected function model(): string
    {
        return (string) config('ai.ollama.model');
    }
}
