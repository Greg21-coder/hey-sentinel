<?php

use App\Models\AccountFollowedApp;
use App\Models\ShopifyApp;
use Database\Seeders\DemoAccountSeeder;
use Database\Seeders\PlanSeeder;

beforeEach(function () {
    $this->seed(PlanSeeder::class);
    $this->seed(DemoAccountSeeder::class);
    $this->user = \App\Models\User::where('email', 'acme.owner@example.test')->firstOrFail();
    $this->account = $this->user->currentAccount;
});

it('dashboard My Apps section shows only kind=mine rows', function () {
    $mineApp = ShopifyApp::factory()->create(['name' => 'My Klaviyo', 'scraping_status' => 'scraped']);
    $competitorApp = ShopifyApp::factory()->create(['name' => 'Some Competitor', 'scraping_status' => 'scraped']);

    AccountFollowedApp::withoutGlobalScope('account')->create([
        'account_id' => $this->account->id,
        'shopify_app_id' => $mineApp->id,
        'kind' => 'mine',
        'followed_at' => now(),
    ]);

    AccountFollowedApp::withoutGlobalScope('account')->create([
        'account_id' => $this->account->id,
        'shopify_app_id' => $competitorApp->id,
        'kind' => 'competitor',
        'followed_at' => now(),
    ]);

    $response = $this->actingAs($this->user)->get('/customer');
    $myApps = $response->viewData('page')['props']['myApps'];
    $names = collect($myApps)->pluck('name')->all();

    expect($names)->toContain('My Klaviyo');
    expect($names)->not->toContain('Some Competitor');
});

it('exposes onboarding.needsTour=true when account has zero mine-apps', function () {
    $response = $this->actingAs($this->user)->get('/customer');
    $onboarding = $response->viewData('page')['props']['onboarding'];

    expect($onboarding['needsTour'])->toBeTrue();
});

it('exposes onboarding.needsTour=false when account has at least one mine-app', function () {
    $app = ShopifyApp::factory()->create(['scraping_status' => 'scraped']);
    AccountFollowedApp::withoutGlobalScope('account')->create([
        'account_id' => $this->account->id,
        'shopify_app_id' => $app->id,
        'kind' => 'mine',
        'followed_at' => now(),
    ]);

    $response = $this->actingAs($this->user)->get('/customer');
    $onboarding = $response->viewData('page')['props']['onboarding'];

    expect($onboarding['needsTour'])->toBeFalse();
});
