<?php

use App\Enums\ScrapingStatus;
use App\Jobs\Scraping\DiscoverAppsFromSitemapJob;
use App\Models\DiscoveryRun;
use App\Models\ShopifyApp;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('scraping.discovery.sitemap_url', 'https://apps.shopify.com/sitemap_apps_en.xml');
});

it('discovers new apps from sitemap and records stats', function () {
    $xml = file_get_contents(base_path('tests/Fixtures/Scraping/sitemap-apps-sample.xml'));
    Http::fake([
        'apps.shopify.com/*' => Http::response($xml, 200),
    ]);

    $run = DiscoveryRun::create([
        'source' => 'sitemap',
        'status' => 'pending',
        'triggered_by' => 'manual',
        'apps_limit' => 500,
    ]);

    (new DiscoverAppsFromSitemapJob($run))->handle(app(\App\Services\Scraping\ShopifySitemapParser::class));

    $run->refresh();
    expect($run->status)->toBe('completed');
    expect($run->apps_found)->toBe(3);
    expect($run->apps_new)->toBe(3);
    expect($run->apps_existing)->toBe(0);
    expect($run->started_at)->not->toBeNull();
    expect($run->completed_at)->not->toBeNull();

    $app = ShopifyApp::where('shopify_app_handle', 'klaviyo-email-marketing')->first();
    expect($app)->not->toBeNull();
    expect($app->scraping_status)->toBe(ScrapingStatus::Pending);
    expect($app->developer_name)->toBe('pending-discovery');
});

it('does not overwrite existing apps', function () {
    ShopifyApp::create([
        'shopify_app_handle' => 'loox',
        'name' => 'Loox Original',
        'developer_name' => 'Loox Inc',
        'scraping_status' => ScrapingStatus::Scraped->value,
    ]);

    $xml = file_get_contents(base_path('tests/Fixtures/Scraping/sitemap-apps-sample.xml'));
    Http::fake([
        'apps.shopify.com/*' => Http::response($xml, 200),
    ]);

    $run = DiscoveryRun::create([
        'source' => 'sitemap',
        'status' => 'pending',
        'triggered_by' => 'cron',
        'apps_limit' => 500,
    ]);

    (new DiscoverAppsFromSitemapJob($run))->handle(app(\App\Services\Scraping\ShopifySitemapParser::class));

    $run->refresh();
    expect($run->apps_new)->toBe(2);
    expect($run->apps_existing)->toBe(1);

    $loox = ShopifyApp::where('shopify_app_handle', 'loox')->first();
    expect($loox->name)->toBe('Loox Original');
    expect($loox->developer_name)->toBe('Loox Inc');
    expect($loox->scraping_status)->toBe(ScrapingStatus::Scraped);
});

it('respects apps_limit for new insertions', function () {
    $xml = file_get_contents(base_path('tests/Fixtures/Scraping/sitemap-apps-sample.xml'));
    Http::fake([
        'apps.shopify.com/*' => Http::response($xml, 200),
    ]);

    $run = DiscoveryRun::create([
        'source' => 'sitemap',
        'status' => 'pending',
        'triggered_by' => 'manual',
        'apps_limit' => 1,
    ]);

    (new DiscoverAppsFromSitemapJob($run))->handle(app(\App\Services\Scraping\ShopifySitemapParser::class));

    $run->refresh();
    expect($run->apps_found)->toBe(3);
    expect($run->apps_new)->toBe(1);
    expect($run->status)->toBe('completed');
    expect(ShopifyApp::count())->toBe(1);
});

it('marks run as failed on HTTP error', function () {
    Http::fake([
        'apps.shopify.com/*' => Http::response('Server Error', 500),
    ]);

    $run = DiscoveryRun::create([
        'source' => 'sitemap',
        'status' => 'pending',
        'triggered_by' => 'cron',
        'apps_limit' => 500,
    ]);

    (new DiscoverAppsFromSitemapJob($run))->handle(app(\App\Services\Scraping\ShopifySitemapParser::class));

    $run->refresh();
    expect($run->status)->toBe('failed');
    expect($run->error_message)->not->toBeNull();
});
