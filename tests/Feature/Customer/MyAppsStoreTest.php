<?php

use App\Models\AccountFollowedApp;
use App\Models\ShopifyApp;
use Database\Seeders\DemoAccountSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->seed(PlanSeeder::class);
    $this->seed(DemoAccountSeeder::class);
    $this->user = \App\Models\User::where('email', 'acme.owner@example.test')->firstOrFail();
    $this->account = $this->user->currentAccount;
});

it('marks an already-scraped app as mine when handle is provided', function () {
    $app = ShopifyApp::factory()->create([
        'shopify_app_handle' => 'klaviyo-email-marketing',
        'name' => 'Klaviyo',
        'scraping_status' => 'scraped',
    ]);

    $this->actingAs($this->user)
        ->post('/customer/my-apps', ['handle' => 'klaviyo-email-marketing'])
        ->assertRedirect('/customer/my-apps');

    $pivot = AccountFollowedApp::withoutGlobalScope('account')
        ->where('shopify_app_id', $app->id)
        ->first();

    expect($pivot)->not->toBeNull();
    expect($pivot->kind->value)->toBe('mine');
});

it('is idempotent — POSTing the same handle twice does not create duplicate pivots', function () {
    ShopifyApp::factory()->create([
        'shopify_app_handle' => 'foo-app',
        'scraping_status' => 'scraped',
    ]);

    $this->actingAs($this->user)->post('/customer/my-apps', ['handle' => 'foo-app']);
    $this->actingAs($this->user)->post('/customer/my-apps', ['handle' => 'foo-app']);

    $count = AccountFollowedApp::withoutGlobalScope('account')
        ->where('account_id', $this->account->id)
        ->where('kind', 'mine')
        ->count();

    expect($count)->toBe(1);
});

it('imports a pending app synchronously via the import service', function () {
    $html = file_get_contents(base_path('tests/Fixtures/Scraping/app-klaviyo.html'));
    Http::fake(['apps.shopify.com/*' => Http::response($html, 200)]);

    ShopifyApp::factory()->create([
        'shopify_app_handle' => 'klaviyo-email-marketing',
        'name' => 'klaviyo-email-marketing',
        'scraping_status' => 'pending',
    ]);

    $this->actingAs($this->user)
        ->post('/customer/my-apps', ['handle' => 'klaviyo-email-marketing'])
        ->assertRedirect('/customer/my-apps');

    $app = ShopifyApp::where('shopify_app_handle', 'klaviyo-email-marketing')->first();
    expect($app->scraping_status->value)->toBe('scraped');
    expect($app->name)->toBe('Klaviyo: Email Marketing & SMS');
});

it('accepts a full Shopify URL, extracts the handle, and imports if unknown', function () {
    $html = file_get_contents(base_path('tests/Fixtures/Scraping/app-klaviyo.html'));
    Http::fake(['apps.shopify.com/*' => Http::response($html, 200)]);

    $this->actingAs($this->user)
        ->post('/customer/my-apps', ['url' => 'https://apps.shopify.com/brand-new-app'])
        ->assertRedirect('/customer/my-apps');

    $app = ShopifyApp::where('shopify_app_handle', 'brand-new-app')->first();
    expect($app)->not->toBeNull();
    expect($app->scraping_status->value)->toBe('scraped');

    $pivot = AccountFollowedApp::withoutGlobalScope('account')
        ->where('shopify_app_id', $app->id)
        ->first();
    expect($pivot->kind->value)->toBe('mine');
});

it('sets reviews_sync_started_at when importing a fresh app', function () {
    $html = file_get_contents(base_path('tests/Fixtures/Scraping/app-klaviyo.html'));
    Http::fake(['apps.shopify.com/*' => Http::response($html, 200)]);

    $this->actingAs($this->user)
        ->post('/customer/my-apps', ['url' => 'https://apps.shopify.com/sync-start-test-app']);

    $app = ShopifyApp::where('shopify_app_handle', 'sync-start-test-app')->first();
    expect($app->reviews_sync_started_at)->not->toBeNull();
    expect($app->reviews_sync_started_at->diffInSeconds(now()))->toBeLessThan(5);
});

it('rejects a non-Shopify URL with 422', function () {
    $this->actingAs($this->user)
        ->post('/customer/my-apps', ['url' => 'https://evil.example.com/foo'])
        ->assertSessionHasErrors('url');
});

it('rejects a request with neither handle nor url', function () {
    $this->actingAs($this->user)
        ->post('/customer/my-apps', [])
        ->assertSessionHasErrors();
});

it('blocks with 403 when plan apps_tracked quota is exhausted', function () {
    $gamma = \App\Models\User::where('email', 'gamma.owner@example.test')->firstOrFail();
    $gammaAccount = $gamma->currentAccount;

    while ($gammaAccount->canUse('apps_tracked')) {
        $a = ShopifyApp::factory()->create(['scraping_status' => 'scraped']);
        AccountFollowedApp::withoutGlobalScope('account')->create([
            'account_id' => $gammaAccount->id,
            'shopify_app_id' => $a->id,
            'kind' => 'competitor',
            'followed_at' => now(),
        ]);
        $gammaAccount->recordUsage('apps_tracked');
    }

    $newApp = ShopifyApp::factory()->create([
        'shopify_app_handle' => 'one-too-many',
        'scraping_status' => 'scraped',
    ]);

    $this->actingAs($gamma)
        ->post('/customer/my-apps', ['handle' => 'one-too-many'])
        ->assertStatus(403);
});
