<?php

use App\Models\AccountFollowedApp;
use App\Models\ShopifyApp;
use App\Models\ShopifyAppCategory;
use Database\Seeders\DemoAccountSeeder;
use Database\Seeders\PlanSeeder;

beforeEach(function () {
    $this->seed(PlanSeeder::class);
    $this->seed(DemoAccountSeeder::class);
    $this->user = \App\Models\User::where('email', 'acme.owner@example.test')->firstOrFail();
    $this->account = $this->user->currentAccount;
    $this->cat = ShopifyAppCategory::create(['slug' => 'marketing', 'name' => 'Marketing']);

    $this->mine = ShopifyApp::factory()->withFeatures()->create(['category_id' => $this->cat->id, 'average_rating' => 4.80, 'total_reviews' => 100]);
    $this->comp = ShopifyApp::factory()->withFeatures()->create(['category_id' => $this->cat->id, 'average_rating' => 4.50, 'total_reviews' => 100]);

    AccountFollowedApp::factory()->mine($this->account)->create(['shopify_app_id' => $this->mine->id]);
    AccountFollowedApp::factory()->competitor($this->account)->create(['shopify_app_id' => $this->comp->id]);
});

it('renders versus page with columns and rows', function () {
    $response = $this->actingAs($this->user)->get('/customer/my-apps/versus?mine='.$this->mine->id.'&competitors[]='.$this->comp->id);
    $response->assertOk();
    $props = $response->viewData('page')['props']['versus'];
    expect($props['columns'])->toHaveCount(2);
    expect($props['rows'])->not->toBeEmpty();
    $rating = collect($props['rows'])->firstWhere('metric', 'rating');
    expect($rating['winner_index'])->toBe(0);
});

it('renders an empty selection state when no competitors are provided', function () {
    $response = $this->actingAs($this->user)->get('/customer/my-apps/versus?mine='.$this->mine->id);
    $response->assertOk();
    $props = $response->viewData('page')['props']['versus'];
    expect($props['columns'])->toHaveCount(1);
});
