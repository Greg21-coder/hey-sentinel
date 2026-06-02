<?php

use App\Enums\ScrapingStatus;
use App\Models\AppSnapshot;
use App\Models\ShopifyApp;
use App\Services\Scraping\ShopifyAppImportService;
use Illuminate\Support\Facades\Http;

it('imports a brand-new app via handle, persists row, seeds snapshot', function () {
    $html = file_get_contents(base_path('tests/Fixtures/Scraping/app-klaviyo.html'));
    Http::fake([
        'apps.shopify.com/*' => Http::response($html, 200),
    ]);

    $service = app(ShopifyAppImportService::class);
    $app = $service->importByHandle('klaviyo-email-marketing');

    expect($app)->toBeInstanceOf(ShopifyApp::class);
    expect($app->shopify_app_handle)->toBe('klaviyo-email-marketing');
    expect($app->name)->toBe('Klaviyo: Email Marketing & SMS');
    expect($app->scraping_status)->toBe(ScrapingStatus::Scraped);

    expect(AppSnapshot::where('shopify_app_id', $app->id)->count())->toBe(1);
});

it('is idempotent on a second call — updates the existing row', function () {
    $html = file_get_contents(base_path('tests/Fixtures/Scraping/app-klaviyo.html'));
    Http::fake([
        'apps.shopify.com/*' => Http::response($html, 200),
    ]);

    $service = app(ShopifyAppImportService::class);
    $first = $service->importByHandle('klaviyo-email-marketing');
    $second = $service->importByHandle('klaviyo-email-marketing');

    expect($second->id)->toBe($first->id);
    expect(ShopifyApp::where('shopify_app_handle', 'klaviyo-email-marketing')->count())->toBe(1);
    // Second call seeds a second snapshot row — that's fine for diff baseline
    expect(AppSnapshot::where('shopify_app_id', $first->id)->count())->toBe(2);
});

it('throws when the scraper returns null (rate-limited / unreachable)', function () {
    Http::fake([
        'apps.shopify.com/*' => Http::response('', 429),
    ]);

    $service = app(ShopifyAppImportService::class);

    expect(fn () => $service->importByHandle('does-not-matter'))
        ->toThrow(\RuntimeException::class);
});

it('throws on HTTP failure (e.g. 500)', function () {
    Http::fake([
        'apps.shopify.com/*' => Http::response('boom', 500),
    ]);

    $service = app(ShopifyAppImportService::class);

    expect(fn () => $service->importByHandle('broken-app'))
        ->toThrow(\RuntimeException::class);
});
