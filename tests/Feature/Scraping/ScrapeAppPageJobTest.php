<?php

use App\Enums\ScrapingStatus;
use App\Jobs\Scraping\ScrapeAppPageJob;
use App\Models\ShopifyApp;
use App\Services\Scraping\ShopifyAppScraper;
use Illuminate\Support\Facades\Http;

it('upserts a ShopifyApp row with status=scraped when fetch succeeds', function () {
    Http::fake([
        'apps.shopify.com/*' => Http::response('<html>fake page</html>', 200),
    ]);

    (new ScrapeAppPageJob('cool-app'))->handle(app(ShopifyAppScraper::class));

    $app = ShopifyApp::where('shopify_app_handle', 'cool-app')->first();
    expect($app)->not->toBeNull();
    expect($app->scraping_status)->toBe(ScrapingStatus::Scraped);
    expect($app->last_scraped_at)->not->toBeNull();
});

it('marks status=error on HTTP 500', function () {
    Http::fake([
        'apps.shopify.com/*' => Http::response('boom', 500),
    ]);

    (new ScrapeAppPageJob('broken-app'))->handle(app(ShopifyAppScraper::class));

    $app = ShopifyApp::where('shopify_app_handle', 'broken-app')->first();
    expect($app->scraping_status)->toBe(ScrapingStatus::Error);
    expect($app->scraping_error)->toContain('500');
});
