<?php

namespace App\Services\Ai;

use App\Enums\AiStatus;
use App\Exceptions\Ai\FeatureExtractionFailed;
use App\Models\ShopifyApp;
use App\Models\StoreReview;
use App\Support\Ai\AppFeaturePrompt;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AppFeatureExtractionService
{
    public function extract(ShopifyApp $app): void
    {
        $reviews = StoreReview::query()
            ->where('shopify_app_id', $app->id)
            ->where('ai_status', AiStatus::Processed->value)
            ->orderByDesc('published_at')
            ->limit(AppFeaturePrompt::SAMPLE_SIZE)
            ->get();

        if ($reviews->count() < AppFeaturePrompt::MIN_SAMPLE_SIZE) {
            Log::info('AppFeatureExtractionService skipped: insufficient processed reviews', [
                'app_id' => $app->id,
                'count' => $reviews->count(),
            ]);

            return;
        }

        $response = $this->http()->post('/api/chat', [
            'model' => $this->model(),
            'stream' => false,
            'format' => 'json',
            'options' => [
                'temperature' => (float) config('ai.ollama.temperature', 0.2),
                'num_predict' => 700,
            ],
            'messages' => [
                ['role' => 'system', 'content' => AppFeaturePrompt::system()],
                ['role' => 'user', 'content' => AppFeaturePrompt::userMessage($app, $reviews)],
            ],
        ]);

        if ($response->failed()) {
            throw new FeatureExtractionFailed(
                "AppFeatureExtractionService HTTP {$response->status()} for app {$app->id}: ".$response->body()
            );
        }

        $raw = (string) $response->json('message.content');
        $decoded = json_decode($raw, true);

        if (! is_array($decoded) || ! isset($decoded['features']) || ! is_array($decoded['features'])) {
            throw new FeatureExtractionFailed("AppFeatureExtractionService invalid JSON for app {$app->id}: {$raw}");
        }

        $features = collect($decoded['features'])
            ->filter(fn ($f) => isset($f['name'], $f['category'], $f['confidence']))
            ->map(fn ($f) => [
                'name' => (string) $f['name'],
                'category' => (string) $f['category'],
                'confidence' => (float) $f['confidence'],
                'source' => 'description+reviews',
            ])
            ->values()
            ->all();

        $app->update([
            'features_json' => $features,
            'features_extracted_at' => now(),
            'features_extraction_model' => $this->model(),
        ]);

        Log::channel('ai')->info('versus.features_extracted', [
            'app_id' => $app->id,
            'feature_count' => count($features),
            'model' => $this->model(),
        ]);
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
