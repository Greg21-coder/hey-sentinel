<?php

use App\Enums\FollowedAppKind;
use App\Jobs\Scraping\ScrapeReviewPageJob;
use App\Models\AccountFollowedApp;
use App\Models\ShopifyApp;
use Database\Seeders\DemoAccountSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    $this->seed(PlanSeeder::class);
    $this->seed(DemoAccountSeeder::class);
    $this->user = \App\Models\User::where('email', 'acme.owner@example.test')->firstOrFail();
    $this->account = $this->user->currentAccount;
});

it('dispatches review jobs and updates the timestamp when the user follows the app', function () {
    Queue::fake();
    $app = ShopifyApp::factory()->create([
        'scraping_status' => 'scraped',
        'reviews_sync_started_at' => now()->subDay(),
    ]);
    AccountFollowedApp::withoutGlobalScope('account')->create([
        'account_id' => $this->account->id,
        'shopify_app_id' => $app->id,
        'kind' => FollowedAppKind::Mine->value,
        'followed_at' => now(),
    ]);

    $this->actingAs($this->user)
        ->post("/customer/apps/{$app->id}/sync-reviews")
        ->assertRedirect();

    Queue::assertPushed(ScrapeReviewPageJob::class, 3);

    $app->refresh();
    expect($app->reviews_sync_started_at->diffInSeconds(now()))->toBeLessThan(5);
});

it('rejects with 403 when the user does not follow the app', function () {
    Queue::fake();
    $app = ShopifyApp::factory()->create(['scraping_status' => 'scraped']);

    $this->actingAs($this->user)
        ->post("/customer/apps/{$app->id}/sync-reviews")
        ->assertStatus(403);

    Queue::assertNothingPushed();
});

it('rate limits a second sync within 5 minutes per app', function () {
    Queue::fake();

    $app = ShopifyApp::factory()->create(['scraping_status' => 'scraped']);
    RateLimiter::clear("manual-sync-reviews:app:{$app->id}");

    AccountFollowedApp::withoutGlobalScope('account')->create([
        'account_id' => $this->account->id,
        'shopify_app_id' => $app->id,
        'kind' => FollowedAppKind::Mine->value,
        'followed_at' => now(),
    ]);

    $this->actingAs($this->user)->post("/customer/apps/{$app->id}/sync-reviews");
    $response = $this->actingAs($this->user)->post("/customer/apps/{$app->id}/sync-reviews");

    $response->assertRedirect();
    $response->assertSessionHas('error');

    Queue::assertPushed(ScrapeReviewPageJob::class, 3);
});
