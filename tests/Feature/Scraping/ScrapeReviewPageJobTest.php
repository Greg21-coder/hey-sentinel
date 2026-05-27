<?php

use App\Jobs\Ai\CompileBatchJob;
use App\Jobs\Scraping\ScrapeReviewPageJob;
use App\Models\ShopifyApp;
use App\Models\StoreReview;
use App\Services\Scraping\ShopifyReviewPageParser;
use App\Services\Scraping\ShopifyReviewScraper;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

it('parses reviews from Klaviyo page-1 fixture and inserts deduped rows', function () {
    Bus::fake([ScrapeReviewPageJob::class, CompileBatchJob::class]);
    $app = ShopifyApp::factory()->create(['shopify_app_handle' => 'klaviyo-email-marketing']);

    $html = file_get_contents(base_path('tests/Fixtures/Scraping/reviews-klaviyo-page-1.html'));
    Http::fake([
        'apps.shopify.com/*' => Http::response($html, 200),
    ]);

    (new ScrapeReviewPageJob($app->id, 1))
        ->handle(app(ShopifyReviewScraper::class), app(ShopifyReviewPageParser::class));

    $count = StoreReview::where('shopify_app_id', $app->id)->count();
    expect($count)->toBeGreaterThanOrEqual(5);

    // Re-running the same page should be idempotent (insertOrIgnore on unique key).
    (new ScrapeReviewPageJob($app->id, 1))
        ->handle(app(ShopifyReviewScraper::class), app(ShopifyReviewPageParser::class));

    expect(StoreReview::where('shopify_app_id', $app->id)->count())->toBe($count);

    // Pagination chains: page 1 must dispatch page 2 since fixture has rel="next".
    Bus::assertDispatched(ScrapeReviewPageJob::class, fn ($job) => $job->page === 2);
});
