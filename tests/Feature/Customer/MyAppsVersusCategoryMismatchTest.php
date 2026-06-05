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

    $catA = ShopifyAppCategory::create(['slug' => 'a', 'name' => 'A']);
    $catB = ShopifyAppCategory::create(['slug' => 'b', 'name' => 'B']);

    $this->mine = ShopifyApp::factory()->create(['category_id' => $catA->id]);
    $this->same = ShopifyApp::factory()->create(['category_id' => $catA->id]);
    $this->diff = ShopifyApp::factory()->create(['category_id' => $catB->id]);

    AccountFollowedApp::factory()->mine($this->account)->create(['shopify_app_id' => $this->mine->id]);
    AccountFollowedApp::factory()->competitor($this->account)->create(['shopify_app_id' => $this->same->id]);
    AccountFollowedApp::factory()->competitor($this->account)->create(['shopify_app_id' => $this->diff->id]);
});

it('marks the off-category competitor with category_mismatch=true', function () {
    $url = '/customer/my-apps/versus?mine='.$this->mine->id.'&'.http_build_query([
        'competitors' => [$this->same->id, $this->diff->id],
    ]);

    $response = $this->actingAs($this->user)->get($url);
    $props = $response->viewData('page')['props']['versus'];

    expect($props['columns'][0]['category_mismatch'])->toBeFalse();
    expect($props['columns'][1]['category_mismatch'])->toBeFalse();
    expect($props['columns'][2]['category_mismatch'])->toBeTrue();
});
