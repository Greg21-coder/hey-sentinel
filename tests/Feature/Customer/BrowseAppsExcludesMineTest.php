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

it('excludes apps the current account has marked mine from the browse catalogue', function () {
    $mineApp = ShopifyApp::factory()->create(['name' => 'My Own App', 'scraping_status' => 'scraped']);
    $competitorApp = ShopifyApp::factory()->create(['name' => 'Competitor App', 'scraping_status' => 'scraped']);
    $unrelatedApp = ShopifyApp::factory()->create(['name' => 'Random App', 'scraping_status' => 'scraped']);

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

    $response = $this->actingAs($this->user)->get('/customer/apps');

    $appsOnPage = collect($response->viewData('page')['props']['apps']['data'])->pluck('id')->all();

    expect($appsOnPage)->not->toContain($mineApp->id);
    expect($appsOnPage)->toContain($competitorApp->id);
    expect($appsOnPage)->toContain($unrelatedApp->id);
});
