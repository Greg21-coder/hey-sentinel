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

    public int $tries = 3;

    public int $backoff = 30;

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

        $parsed = $parser->parse($response->body());

        ShopifyApp::updateOrCreate(
            ['shopify_app_handle' => $this->handle],
            [
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

        Log::info('ScrapeAppPageJob success', [
            'handle' => $this->handle,
            'name' => $parsed->name,
            'rating' => $parsed->averageRating,
            'reviews' => $parsed->totalReviews,
        ]);
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
