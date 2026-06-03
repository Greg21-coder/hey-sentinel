<?php

namespace App\Services\Scraping;

use App\Enums\ScrapingStatus;
use App\Jobs\Scraping\ScrapeReviewPageJob;
use App\Models\ShopifyApp;
use App\Models\ShopifyAppCategory;
use App\Services\Intelligence\SnapshotDiffService;
use Illuminate\Support\Str;
use RuntimeException;

class ShopifyAppImportService
{
    public function __construct(
        protected ShopifyAppScraper $scraper,
        protected ShopifyAppPageParser $parser,
        protected SnapshotDiffService $snapshots,
    ) {}

    /**
     * Synchronously fetch + parse + persist a Shopify app by handle.
     * Idempotent: same handle returns the same ShopifyApp row.
     * Seeds an AppSnapshot so Growth Intelligence has a baseline.
     *
     * @throws RuntimeException on fetch/parse failure
     */
    public function importByHandle(string $handle): ShopifyApp
    {
        $response = $this->scraper->fetch($handle);

        if ($response === null) {
            throw new RuntimeException("Could not fetch Shopify app '{$handle}' (rate-limited or unreachable).");
        }

        if ($response->failed()) {
            throw new RuntimeException("Shopify returned HTTP {$response->status()} for '{$handle}'.");
        }

        try {
            $parsed = $this->parser->parse($response->body());
        } catch (\Throwable $e) {
            throw new RuntimeException("Failed to parse Shopify app page for '{$handle}': {$e->getMessage()}", 0, $e);
        }

        $app = ShopifyApp::updateOrCreate(
            ['shopify_app_handle' => $handle],
            [
                'name' => $parsed->name ?? $handle,
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

        $app->load('category');
        $this->snapshots->createSnapshot($app);

        $reviewPages = (int) config('scraping.defaults.review_pages_per_app', 3);

        $app->forceFill(['reviews_sync_started_at' => now()])->save();

        for ($p = 1; $p <= $reviewPages; $p++) {
            ScrapeReviewPageJob::dispatch($app->id, $p);
        }

        return $app;
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
