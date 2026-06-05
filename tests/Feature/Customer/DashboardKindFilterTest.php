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

    $this->mineApp = ShopifyApp::factory()->create(['scraping_status' => 'scraped', 'name' => 'Mine Co']);
    $this->competitorApp = ShopifyApp::factory()->create(['scraping_status' => 'scraped', 'name' => 'Rival Co']);

    AccountFollowedApp::factory()->mine($this->account)->create(['shopify_app_id' => $this->mineApp->id]);
    AccountFollowedApp::factory()->competitor($this->account)->create(['shopify_app_id' => $this->competitorApp->id]);
});

it('defaults to kind=mine on Dashboard', function () {
    $response = $this->actingAs($this->user)->get('/customer/');
    $myApps = $response->viewData('page')['props']['myApps'];
    expect(collect($myApps)->pluck('name')->all())->toBe(['Mine Co']);
});

it('respects ?kind=competitor on Dashboard', function () {
    $response = $this->actingAs($this->user)->get('/customer/?kind=competitor');
    $myApps = $response->viewData('page')['props']['myApps'];
    expect(collect($myApps)->pluck('name')->all())->toBe(['Rival Co']);
});

it('keeps needsTour=false when mine apps exist regardless of chip', function () {
    $response = $this->actingAs($this->user)->get('/customer/?kind=competitor');
    expect($response->viewData('page')['props']['onboarding']['needsTour'])->toBeFalse();
});
