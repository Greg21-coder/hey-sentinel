<?php

use App\Enums\ScrapingStatus;
use App\Jobs\Scraping\ScrapeAppPageJob;
use App\Models\ShopifyApp;
use App\Services\Scraping\ShopifyAppPageParser;
use App\Services\Scraping\ShopifyAppScraper;
use Illuminate\Support\Facades\Http;

it('parses and upserts a ShopifyApp row from a real Klaviyo fixture', function () {
    $html = file_get_contents(base_path('tests/Fixtures/Scraping/app-klaviyo.html'));
    Http::fake([
        'apps.shopify.com/*' => Http::response($html, 200),
    ]);

    (new ScrapeAppPageJob('klaviyo-email-marketing'))
        ->handle(app(ShopifyAppScraper::class), app(ShopifyAppPageParser::class));

    $app = ShopifyApp::where('shopify_app_handle', 'klaviyo-email-marketing')->first();
    expect($app)->not->toBeNull();
    expect($app->name)->toBe('Klaviyo: Email Marketing & SMS');
    expect($app->developer_name)->toBe('Klaviyo');
    expect($app->scraping_status)->toBe(ScrapingStatus::Scraped);
    expect((float) $app->average_rating)->toBe(4.6);
    expect($app->total_reviews)->toBe(2766);
    expect($app->pricing_has_free)->toBeTrue();
    expect($app->category)->not->toBeNull();
    expect($app->category->name)->toBe('Email marketing');
    expect($app->last_scraped_at)->not->toBeNull();
});

it('marks status=error on HTTP 500', function () {
    Http::fake([
        'apps.shopify.com/*' => Http::response('boom', 500),
    ]);

    (new ScrapeAppPageJob('broken-app'))
        ->handle(app(ShopifyAppScraper::class), app(ShopifyAppPageParser::class));

    $app = ShopifyApp::where('shopify_app_handle', 'broken-app')->first();
    expect($app->scraping_status)->toBe(ScrapingStatus::Error);
    expect($app->scraping_error)->toContain('500');
});
