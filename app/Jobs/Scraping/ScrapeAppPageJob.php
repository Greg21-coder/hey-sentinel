<?php

namespace App\Jobs\Scraping;

use App\Enums\ScrapingStatus;
use App\Models\ShopifyApp;
use App\Services\Scraping\ShopifyAppScraper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScrapeAppPageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public string $handle) {}

    public function middleware(): array
    {
        return [(new RateLimited('shopify-scrape'))];
    }

    public function handle(ShopifyAppScraper $scraper): void
    {
        $response = $scraper->fetch($this->handle);
        if ($response === null) {
            // Proxy returned 429/503 and was put on cooldown — release for retry.
            $this->release(60);

            return;
        }

        if ($response->failed()) {
            ShopifyApp::updateOrCreate(
                ['shopify_app_handle' => $this->handle],
                [
                    'name' => $this->handle,
                    'developer_name' => 'unknown',
                    'scraping_status' => ScrapingStatus::Error->value,
                    'scraping_error' => "HTTP {$response->status()}",
                    'last_scraped_at' => now(),
                ]
            );

            return;
        }

        // Parsing the HTML is deferred to the parser layer. For Phase 3 we
        // persist the raw page hash + status so the pipeline observes work
        // happened. A dedicated HTML parser is added when the structure
        // of the Shopify App Store page is reverse-engineered against a
        // sample fixture.
        ShopifyApp::updateOrCreate(
            ['shopify_app_handle' => $this->handle],
            [
                'name' => $this->handle,
                'developer_name' => 'pending-parse',
                'scraping_status' => ScrapingStatus::Scraped->value,
                'last_scraped_at' => now(),
            ]
        );

        Log::info('ScrapeAppPageJob success', ['handle' => $this->handle]);
    }
}
