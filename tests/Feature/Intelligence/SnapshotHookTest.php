<?php

use App\Enums\ScrapingStatus;
use App\Jobs\Scraping\ScrapeAppPageJob;
use App\Jobs\Scraping\ScrapeReviewPageJob;
use App\Models\Account;
use App\Models\AccountFollowedApp;
use App\Models\AppChange;
use App\Models\AppChangeNotification;
use App\Models\AppSnapshot;
use App\Models\ShopifyApp;
use App\Services\Scraping\ShopifyAppPageParser;
use App\Services\Scraping\ShopifyAppScraper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake(ScrapeReviewPageJob::class);
    $this->seed(\Database\Seeders\PlanSeeder::class);
});

it('creates a snapshot after successful scrape', function () {
    $html = file_get_contents(base_path('tests/Fixtures/Scraping/app-klaviyo.html'));
    Http::fake(['apps.shopify.com/*' => Http::response($html, 200)]);

    (new ScrapeAppPageJob('klaviyo-email-marketing'))
        ->handle(app(ShopifyAppScraper::class), app(ShopifyAppPageParser::class));

    $app = ShopifyApp::where('shopify_app_handle', 'klaviyo-email-marketing')->first();
    expect(AppSnapshot::where('shopify_app_id', $app->id)->count())->toBe(1);
});

it('detects changes on second scrape and creates notifications', function () {
    $app = ShopifyApp::create([
        'shopify_app_handle' => 'test-change-app',
        'name' => 'Old Name',
        'developer_name' => 'Dev',
        'pricing_raw' => 'Free',
        'average_rating' => 4.50,
        'total_reviews' => 100,
        'scraping_status' => ScrapingStatus::Scraped->value,
    ]);

    AppSnapshot::create([
        'shopify_app_id' => $app->id,
        'name' => 'Old Name',
        'developer_name' => 'Dev',
        'pricing_raw' => 'Free',
        'pricing_has_free' => true,
        'average_rating' => 4.50,
        'total_reviews' => 100,
        'snapshot_at' => now()->subDay(),
    ]);

    $account = Account::factory()->create();
    AccountFollowedApp::create([
        'account_id' => $account->id,
        'shopify_app_id' => $app->id,
        'kind' => 'competitor',
        'followed_at' => now(),
    ]);

    $html = file_get_contents(base_path('tests/Fixtures/Scraping/app-klaviyo.html'));
    Http::fake(['apps.shopify.com/*' => Http::response($html, 200)]);

    (new ScrapeAppPageJob('test-change-app'))
        ->handle(app(ShopifyAppScraper::class), app(ShopifyAppPageParser::class));

    expect(AppSnapshot::where('shopify_app_id', $app->id)->count())->toBe(2);
    expect(AppChange::where('shopify_app_id', $app->id)->count())->toBeGreaterThan(0);
    expect(AppChangeNotification::where('account_id', $account->id)->count())->toBeGreaterThan(0);
});

it('does not create changes on first scrape', function () {
    $html = file_get_contents(base_path('tests/Fixtures/Scraping/app-klaviyo.html'));
    Http::fake(['apps.shopify.com/*' => Http::response($html, 200)]);

    (new ScrapeAppPageJob('first-time-app'))
        ->handle(app(ShopifyAppScraper::class), app(ShopifyAppPageParser::class));

    $app = ShopifyApp::where('shopify_app_handle', 'first-time-app')->first();
    expect(AppSnapshot::where('shopify_app_id', $app->id)->count())->toBe(1);
    expect(AppChange::where('shopify_app_id', $app->id)->count())->toBe(0);
});
