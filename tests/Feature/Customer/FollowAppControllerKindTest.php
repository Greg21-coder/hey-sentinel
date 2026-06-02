<?php

use App\Enums\FollowedAppKind;
use App\Models\AccountFollowedApp;
use App\Models\ShopifyApp;
use Database\Seeders\DemoAccountSeeder;

beforeEach(function () {
    $this->seed(\Database\Seeders\PlanSeeder::class);
    $this->seed(DemoAccountSeeder::class);
    $this->user = \App\Models\User::where('email', 'acme.owner@example.test')->firstOrFail();
    $this->shopifyApp = ShopifyApp::factory()->create(['scraping_status' => 'scraped']);
});

it('defaults kind to competitor when not provided', function () {
    $this->actingAs($this->user)
        ->post("/customer/apps/{$this->shopifyApp->id}/follow")
        ->assertRedirect();

    $pivot = AccountFollowedApp::withoutGlobalScope('account')
        ->where('shopify_app_id', $this->shopifyApp->id)
        ->first();

    expect($pivot)->not->toBeNull();
    expect($pivot->kind)->toBe(FollowedAppKind::Competitor);
});

it('stores kind=mine when explicitly provided', function () {
    $this->actingAs($this->user)
        ->post("/customer/apps/{$this->shopifyApp->id}/follow", ['kind' => 'mine'])
        ->assertRedirect();

    $pivot = AccountFollowedApp::withoutGlobalScope('account')
        ->where('shopify_app_id', $this->shopifyApp->id)
        ->first();

    expect($pivot->kind)->toBe(FollowedAppKind::Mine);
});

it('rejects an invalid kind value', function () {
    $this->actingAs($this->user)
        ->post("/customer/apps/{$this->shopifyApp->id}/follow", ['kind' => 'bogus'])
        ->assertSessionHasErrors('kind');
});

it('does not double-count apps_tracked usage when the same app is followed twice', function () {
    $this->actingAs($this->user)
        ->post("/customer/apps/{$this->shopifyApp->id}/follow")
        ->assertRedirect();

    $this->actingAs($this->user)
        ->post("/customer/apps/{$this->shopifyApp->id}/follow")
        ->assertRedirect();

    $usageLogCount = \App\Models\FeatureUsageLog::query()
        ->where('account_id', $this->user->currentAccount->id)
        ->where('feature_key', 'apps_tracked')
        ->count();

    expect($usageLogCount)->toBe(1);
});
