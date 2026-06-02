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

it('returns scraped apps before pending apps for a matching query', function () {
    ShopifyApp::factory()->create(['name' => 'Klaviyo Email', 'scraping_status' => 'scraped', 'total_reviews' => 100]);
    ShopifyApp::factory()->create([
        'shopify_app_handle' => 'klaviyo-old',
        'name' => 'klaviyo-old',
        'scraping_status' => 'pending',
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/customer/my-apps/search?q=klaviyo');

    $response->assertOk();
    $results = $response->json('results');

    expect($results)->toHaveCount(2);
    expect($results[0]['scraping_status'])->toBe('scraped');
    expect($results[1]['scraping_status'])->toBe('pending');
});

it('excludes apps already marked mine for the current account', function () {
    $mine = ShopifyApp::factory()->create(['name' => 'Foo Mine', 'scraping_status' => 'scraped']);
    $other = ShopifyApp::factory()->create(['name' => 'Foo Other', 'scraping_status' => 'scraped']);

    AccountFollowedApp::withoutGlobalScope('account')->create([
        'account_id' => $this->account->id,
        'shopify_app_id' => $mine->id,
        'kind' => 'mine',
        'followed_at' => now(),
    ]);

    $response = $this->actingAs($this->user)->getJson('/customer/my-apps/search?q=foo');
    $ids = collect($response->json('results'))->pluck('id')->all();

    expect($ids)->toContain($other->id);
    expect($ids)->not->toContain($mine->id);
});

it('excludes unlisted apps', function () {
    ShopifyApp::factory()->create([
        'name' => 'Dead App',
        'scraping_status' => 'scraped',
        'unlisted_at' => now()->subDay(),
    ]);

    $response = $this->actingAs($this->user)->getJson('/customer/my-apps/search?q=dead');
    expect($response->json('results'))->toBeEmpty();
});

it('returns empty for queries shorter than 2 chars', function () {
    ShopifyApp::factory()->create(['name' => 'A', 'scraping_status' => 'scraped']);

    $response = $this->actingAs($this->user)->getJson('/customer/my-apps/search?q=a');
    expect($response->json('results'))->toBeEmpty();
});

it('matches by shopify_app_handle (for pending apps with no name yet)', function () {
    ShopifyApp::factory()->create([
        'shopify_app_handle' => 'my-cool-handle-app',
        'name' => 'my-cool-handle-app',
        'scraping_status' => 'pending',
    ]);

    $response = $this->actingAs($this->user)->getJson('/customer/my-apps/search?q=cool-handle');
    $results = $response->json('results');

    expect($results)->toHaveCount(1);
    expect($results[0]['shopify_app_handle'])->toBe('my-cool-handle-app');
});

it('renders the MyApps page with the current account mine-apps and onboarding flag', function () {
    $app = ShopifyApp::factory()->create([
        'name' => 'My Klaviyo',
        'scraping_status' => 'scraped',
    ]);

    AccountFollowedApp::withoutGlobalScope('account')->create([
        'account_id' => $this->account->id,
        'shopify_app_id' => $app->id,
        'kind' => 'mine',
        'followed_at' => now(),
    ]);

    $response = $this->actingAs($this->user)->get('/customer/my-apps');
    $response->assertOk();

    $props = $response->viewData('page')['props'];
    expect($props['myApps'])->toHaveCount(1);
    expect($props['myApps'][0]['name'])->toBe('My Klaviyo');
    expect($props['onboarding']['needsTour'])->toBeFalse();
});

it('renders the MyApps page with onboarding.needsTour=true for new accounts', function () {
    $response = $this->actingAs($this->user)->get('/customer/my-apps');
    $props = $response->viewData('page')['props'];

    expect($props['myApps'])->toBeEmpty();
    expect($props['onboarding']['needsTour'])->toBeTrue();
});
