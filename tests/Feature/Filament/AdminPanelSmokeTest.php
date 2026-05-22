<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('shopify-apps index loads and is read-only (no New button)', function () {
    $this->get('/admin/shopify-apps')
        ->assertStatus(200)
        ->assertDontSee('New shopify app', escape: false);
});

it('shopify-stores index loads and is read-only (no New button)', function () {
    $this->get('/admin/shopify-stores')
        ->assertStatus(200)
        ->assertDontSee('New shopify store', escape: false);
});

it('store-reviews index loads and is read-only (no New button)', function () {
    $this->get('/admin/store-reviews')
        ->assertStatus(200)
        ->assertDontSee('New store review', escape: false);
});

it('shopify-apps view page renders for an existing app and preserves the reviews relation', function () {
    $app = App\Models\ShopifyApp::factory()->create();

    $this->get("/admin/shopify-apps/{$app->id}")
        ->assertStatus(200)
        ->assertSee('Reviews');
});
