<?php

namespace App\Jobs\Ai;

use App\Models\ShopifyApp;
use App\Services\Ai\AppFeatureExtractionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExtractAppFeaturesJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 60, 180];
    public int $uniqueFor = 600;

    public function __construct(public readonly int $appId)
    {
    }

    public function uniqueId(): string
    {
        return "app-features:{$this->appId}";
    }

    public function handle(AppFeatureExtractionService $service): void
    {
        $app = ShopifyApp::find($this->appId);
        if (! $app) {
            return;
        }

        if ($this->isFresh($app)) {
            return;
        }

        $service->extract($app);
    }

    public function failed(Throwable $e): void
    {
        $app = ShopifyApp::find($this->appId);
        if (! $app) {
            return;
        }

        $app->update([
            'features_json' => [],
            'features_extracted_at' => now(),
            'features_extraction_model' => (string) config('ai.ollama.model'),
        ]);

        Log::channel('ai')->error('versus.features_extraction_failed', [
            'app_id' => $this->appId,
            'message' => $e->getMessage(),
        ]);
    }

    protected function isFresh(ShopifyApp $app): bool
    {
        if ($app->features_extracted_at === null) {
            return false;
        }
        if ($app->features_extraction_model !== (string) config('ai.ollama.model')) {
            return false;
        }
        if ($app->features_extracted_at->lt(now()->subDays(30))) {
            return false;
        }

        return true;
    }
}
