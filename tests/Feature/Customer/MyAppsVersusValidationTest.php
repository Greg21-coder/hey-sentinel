<?php

use App\Enums\FollowedAppKind;
use App\Models\Account;
use App\Models\AccountFollowedApp;
use App\Models\ShopifyApp;
use Database\Seeders\DemoAccountSeeder;
use Database\Seeders\PlanSeeder;

beforeEach(function () {
    $this->seed(PlanSeeder::class);
    $this->seed(DemoAccountSeeder::class);
    $this->user = \App\Models\User::where('email', 'acme.owner@example.test')->firstOrFail();
    $this->account = $this->user->currentAccount;

    $this->mine = ShopifyApp::factory()->create();
    $this->comp = ShopifyApp::factory()->create();
    AccountFollowedApp::factory()->mine($this->account)->create(['shopify_app_id' => $this->mine->id]);
    AccountFollowedApp::factory()->competitor($this->account)->create(['shopify_app_id' => $this->comp->id]);
});

it('rejects mine app belonging to a different account', function () {
    $other = Account::factory()->create();
    $foreignMine = ShopifyApp::factory()->create();
    AccountFollowedApp::factory()->mine($other)->create(['shopify_app_id' => $foreignMine->id]);

    $response = $this->actingAs($this->user)->get('/customer/my-apps/versus?mine='.$foreignMine->id);
    $response->assertStatus(403);
});

it('rejects when competitor is actually kind=mine for this account', function () {
    $response = $this->actingAs($this->user)->get('/customer/my-apps/versus?mine='.$this->mine->id.'&competitors[]='.$this->mine->id);
    $response->assertStatus(403);
});

it('rejects more than 3 competitors', function () {
    $extras = ShopifyApp::factory()->count(4)->create();
    foreach ($extras as $extra) {
        AccountFollowedApp::factory()->competitor($this->account)->create(['shopify_app_id' => $extra->id]);
    }
    $url = '/customer/my-apps/versus?mine='.$this->mine->id.'&'.http_build_query(['competitors' => $extras->pluck('id')->all()]);

    $response = $this->actingAs($this->user)->get($url);
    $response->assertStatus(422);
});

it('rejects when competitor is not followed by the account', function () {
    $foreign = ShopifyApp::factory()->create();
    $response = $this->actingAs($this->user)->get('/customer/my-apps/versus?mine='.$this->mine->id.'&competitors[]='.$foreign->id);
    $response->assertStatus(403);
});
