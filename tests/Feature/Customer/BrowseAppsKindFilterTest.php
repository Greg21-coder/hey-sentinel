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
    $this->untrackedApp = ShopifyApp::factory()->create(['scraping_status' => 'scraped']);

    AccountFollowedApp::factory()->mine($this->account)->create(['shopify_app_id' => $this->mineApp->id]);
    AccountFollowedApp::factory()->competitor($this->account)->create(['shopify_app_id' => $this->competitorApp->id]);
});

it('includes all apps when no chip is set', function () {
    $response = $this->actingAs($this->user)->get('/customer/apps');
    $ids = collect($response->viewData('page')['props']['apps']['data'])->pluck('id')->sort()->values()->all();
    expect($ids)->toContain($this->mineApp->id, $this->competitorApp->id, $this->untrackedApp->id);
});

it('filters to mine when ?kind=mine', function () {
    $response = $this->actingAs($this->user)->get('/customer/apps?kind=mine');
    $ids = collect($response->viewData('page')['props']['apps']['data'])->pluck('id')->all();
    expect($ids)->toBe([$this->mineApp->id]);
});

it('filters to competitor when ?kind=competitor', function () {
    $response = $this->actingAs($this->user)->get('/customer/apps?kind=competitor');
    $ids = collect($response->viewData('page')['props']['apps']['data'])->pluck('id')->all();
    expect($ids)->toBe([$this->competitorApp->id]);
});
