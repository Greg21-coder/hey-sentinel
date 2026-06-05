<?php

use App\Enums\FollowedAppKind;
use App\Models\AccountFollowedApp;
use App\Models\ShopifyApp;
use Database\Seeders\DemoAccountSeeder;
use Database\Seeders\PlanSeeder;

beforeEach(function () {
    $this->seed(PlanSeeder::class);
    $this->seed(DemoAccountSeeder::class);
    $this->user = \App\Models\User::where('email', 'acme.owner@example.test')->firstOrFail();
    $this->account = $this->user->currentAccount;

    $this->mineApp = ShopifyApp::factory()->create(['scraping_status' => 'scraped']);
    $this->competitorApp = ShopifyApp::factory()->create(['scraping_status' => 'scraped']);

    AccountFollowedApp::withoutGlobalScope('account')->create([
        'account_id' => $this->account->id,
        'shopify_app_id' => $this->mineApp->id,
        'kind' => FollowedAppKind::Mine->value,
        'followed_at' => now(),
    ]);
    AccountFollowedApp::withoutGlobalScope('account')->create([
        'account_id' => $this->account->id,
        'shopify_app_id' => $this->competitorApp->id,
        'kind' => FollowedAppKind::Competitor->value,
        'followed_at' => now(),
    ]);
});

it('returns both kinds when ?kind=all (default)', function () {
    $response = $this->actingAs($this->user)->get('/customer/my-apps');
    $myApps = $response->viewData('page')['props']['myApps'];
    expect($myApps)->toHaveCount(2);
});

it('returns only mine when ?kind=mine', function () {
    $response = $this->actingAs($this->user)->get('/customer/my-apps?kind=mine');
    $myApps = $response->viewData('page')['props']['myApps'];
    expect($myApps)->toHaveCount(1);
    expect($myApps[0]['id'])->toBe($this->mineApp->id);
});

it('returns only competitor when ?kind=competitor', function () {
    $response = $this->actingAs($this->user)->get('/customer/my-apps?kind=competitor');
    $myApps = $response->viewData('page')['props']['myApps'];
    expect($myApps)->toHaveCount(1);
    expect($myApps[0]['id'])->toBe($this->competitorApp->id);
});

it('includes ownership kind on each row', function () {
    $response = $this->actingAs($this->user)->get('/customer/my-apps');
    $myApps = $response->viewData('page')['props']['myApps'];
    $kinds = collect($myApps)->pluck('kind')->sort()->values()->all();
    expect($kinds)->toBe(['competitor', 'mine']);
});
