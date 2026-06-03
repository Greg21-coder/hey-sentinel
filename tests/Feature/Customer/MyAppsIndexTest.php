<?php

use App\Enums\FollowedAppKind;
use App\Models\AccountFollowedApp;
use App\Models\ShopifyApp;
use App\Models\StoreReview;
use Database\Seeders\DemoAccountSeeder;
use Database\Seeders\PlanSeeder;

beforeEach(function () {
    $this->seed(PlanSeeder::class);
    $this->seed(DemoAccountSeeder::class);
    $this->user = \App\Models\User::where('email', 'acme.owner@example.test')->firstOrFail();
    $this->account = $this->user->currentAccount;
});

it('includes scraped_reviews_count and reviews_sync_started_at in the payload', function () {
    $app = ShopifyApp::factory()->create([
        'scraping_status' => 'scraped',
        'total_reviews' => 100,
        'reviews_sync_started_at' => now()->subMinutes(2),
    ]);

    StoreReview::factory()->count(3)->create(['shopify_app_id' => $app->id]);

    AccountFollowedApp::withoutGlobalScope('account')->create([
        'account_id' => $this->account->id,
        'shopify_app_id' => $app->id,
        'kind' => FollowedAppKind::Mine->value,
        'followed_at' => now(),
    ]);

    $response = $this->actingAs($this->user)->get('/customer/my-apps');

    $response->assertOk();
    $myApps = $response->viewData('page')['props']['myApps'];
    expect($myApps)->toHaveCount(1);
    expect($myApps[0]['scraped_reviews_count'])->toBe(3);
    expect($myApps[0]['reviews_sync_started_at'])->not->toBeNull();
    expect($myApps[0]['reviews_sync_started_at'])->toBeString();
    expect($myApps[0]['reviews_sync_started_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
});
