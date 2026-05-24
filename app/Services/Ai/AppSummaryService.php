<?php

namespace App\Services\Ai;

use App\Enums\AiStatus;
use App\Models\ShopifyApp;
use App\Models\StoreReview;
use App\Support\Ai\AppSummaryPrompt;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Single-shot LLM summary generation for a ShopifyApp.
 *
 * Unlike the pain-point pipeline (batched, async, Anthropic-or-Ollama via
 * BatchLlmClient), summaries are a low-volume cosmetic feature (~10 apps,
 * one call each, persistable for days). We call Ollama /api/chat directly
 * to keep this independent of the batch contract.
 */
class AppSummaryService
{
    public function generateFor(ShopifyApp $app): ?string
    {
        $reviews = StoreReview::query()
            ->where('shopify_app_id', $app->id)
            ->where('ai_status', AiStatus::Processed->value)
            ->orderByDesc('published_at')
            ->limit(AppSummaryPrompt::SAMPLE_SIZE)
            ->get();

        if ($reviews->count() < AppSummaryPrompt::MIN_SAMPLE_SIZE) {
            Log::info('AppSummaryService skipped: insufficient processed reviews', [
                'app_id' => $app->id,
                'handle' => $app->shopify_app_handle,
                'count' => $reviews->count(),
                'min' => AppSummaryPrompt::MIN_SAMPLE_SIZE,
            ]);

            // Clear any stale summary persisted from a previous run that had more
            // data (or that the model hallucinated, as happened with Recart).
            if (filled($app->ai_summary)) {
                $app->update([
                    'ai_summary' => null,
                    'ai_summary_at' => null,
                    'ai_summary_model' => null,
                ]);
            }

            return null;
        }

        $system = AppSummaryPrompt::system();
        $userMessage = AppSummaryPrompt::userMessage($app, $reviews);

        $response = $this->http()->post('/api/chat', [
            'model' => $this->model(),
            'stream' => false,
            'options' => [
                'temperature' => (float) config('ai.ollama.temperature', 0.2),
                'num_predict' => 400,
            ],
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $userMessage],
            ],
        ]);

        if ($response->failed()) {
            throw new RuntimeException(
                "AppSummaryService HTTP {$response->status()} for app {$app->id}: ".$response->body()
            );
        }

        $text = $response->json('message.content');
        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException("AppSummaryService empty response for app {$app->id}");
        }

        $summary = trim($text);

        $app->update([
            'ai_summary' => $summary,
            'ai_summary_at' => now(),
            'ai_summary_model' => $this->model(),
        ]);

        Log::info('AppSummaryService success', [
            'app_id' => $app->id,
            'handle' => $app->shopify_app_handle,
            'sample_size' => $reviews->count(),
            'summary_chars' => mb_strlen($summary),
        ]);

        return $summary;
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
