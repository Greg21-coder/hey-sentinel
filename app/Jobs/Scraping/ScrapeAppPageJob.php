<?php

namespace App\Jobs\Scraping;

use App\Enums\ScrapingStatus;
use App\Models\ShopifyApp;
use App\Models\ShopifyAppCategory;
use App\Services\Scraping\ParsedShopifyApp;
use App\Services\Scraping\ShopifyAppPageParser;
use App\Services\Scraping\ShopifyAppScraper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ScrapeAppPageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 30;

    public int $maxExceptions = 3;

    public int $backoff = 30;

    public int $timeout = 90;

    public function __construct(public string $handle) {}

    public function middleware(): array
    {
        return [(new RateLimited('shopify-scrape'))];
    }

    public function handle(ShopifyAppScraper $scraper, ShopifyAppPageParser $parser): void
    {
        $response = $scraper->fetch($this->handle);
        if ($response === null) {
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

        try {
            $parsed = $parser->parse($response->body());
        } catch (\Throwable $e) {
            ShopifyApp::updateOrCreate(
                ['shopify_app_handle' => $this->handle],
                [
                    'name' => $this->handle,
                    'developer_name' => 'unknown',
                    'scraping_status' => ScrapingStatus::Error->value,
                    'scraping_error' => "Parse error: {$e->getMessage()}",
                    'last_scraped_at' => now(),
                ]
            );

            return;
        }

        $canonicalHandle = $this->resolveCanonicalHandle($response);

        $app = ShopifyApp::updateOrCreate(
            ['shopify_app_handle' => $this->handle],
            [
                'canonical_handle' => $canonicalHandle,
                'name' => $parsed->name ?? $this->handle,
                'developer_name' => $parsed->developerName ?? 'unknown',
                'developer_url' => $parsed->developerUrl,
                'category_id' => $this->resolveCategoryId($parsed),
                'description' => $parsed->description,
                'pricing_raw' => $parsed->pricingRaw,
                'pricing_has_free' => $parsed->pricingHasFree,
                'avatar_url' => $parsed->avatarUrl,
                'average_rating' => $parsed->averageRating ?? 0,
                'total_reviews' => $parsed->totalReviews ?? 0,
                'scraping_status' => ScrapingStatus::Scraped->value,
                'scraping_error' => null,
                'last_scraped_at' => now(),
            ]
        );

        $reviewPages = (int) config('scraping.defaults.review_pages_per_app', 3);
        for ($p = 1; $p <= $reviewPages; $p++) {
            ScrapeReviewPageJob::dispatch($app->id, $p);
        }

        Log::info('ScrapeAppPageJob success', [
            'handle' => $this->handle,
            'canonical_handle' => $canonicalHandle,
            'name' => $parsed->name,
            'rating' => $parsed->averageRating,
            'reviews' => $parsed->totalReviews,
        ]);
    }

    /**
     * Detect the canonical handle from the response's effective URI (set by
     * Guzzle when it follows a 301/302). Returns null when no redirect
     * happened — the public handle is then the canonical one. Returns null
     * under Http::fake() too, since the fake stack doesn't set transferStats.
     */
    protected function resolveCanonicalHandle(\Illuminate\Http\Client\Response $response): ?string
    {
        $effectiveUri = $response->effectiveUri();
        if ($effectiveUri === null) {
            return null;
        }

        $segments = array_values(array_filter(explode('/', $effectiveUri->getPath()), 'strlen'));
        $finalHandle = $segments[0] ?? null;

        if ($finalHandle === null || $finalHandle === $this->handle) {
            return null;
        }

        return $finalHandle;
    }

    protected function resolveCategoryId(ParsedShopifyApp $parsed): ?int
    {
        if ($parsed->categoryName === null) {
            return null;
        }

        $slug = Str::slug($parsed->categoryName);

        return ShopifyAppCategory::firstOrCreate(
            ['slug' => $slug],
            ['name' => $parsed->categoryName]
        )->id;
    }
}
