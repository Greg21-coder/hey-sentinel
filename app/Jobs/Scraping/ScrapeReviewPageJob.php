<?php

namespace App\Jobs\Scraping;

use App\Models\ShopifyApp;
use App\Services\Scraping\ShopifyReviewScraper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScrapeReviewPageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $appId, public int $page = 1) {}

    public function middleware(): array
    {
        return [(new RateLimited('shopify-scrape'))];
    }

    public function handle(ShopifyReviewScraper $scraper): void
    {
        $app = ShopifyApp::findOrFail($this->appId);

        $response = $scraper->fetch($app->shopify_app_handle, $this->page);
        if ($response === null) {
            $this->release(60);

            return;
        }

        if ($response->failed()) {
            Log::warning('ScrapeReviewPageJob failed', [
                'app_id' => $app->id,
                'handle' => $app->shopify_app_handle,
                'page' => $this->page,
                'status' => $response->status(),
            ]);

            return;
        }

        // Parsing + persistence of individual reviews lives behind the
        // dedicated parser. Phase 3 wires the request loop; review-extraction
        // is the next layer added against fixture HTML.
        Log::info('ScrapeReviewPageJob success', [
            'app_id' => $app->id,
            'page' => $this->page,
            'bytes' => strlen($response->body()),
        ]);
    }
}
