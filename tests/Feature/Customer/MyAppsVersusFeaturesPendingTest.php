<?php

use App\Jobs\Ai\ExtractAppFeaturesJob;
use App\Models\AccountFollowedApp;
use App\Models\ShopifyApp;
use Database\Seeders\DemoAccountSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed(PlanSeeder::class);
    $this->seed(DemoAccountSeeder::class);
    $this->user = \App\Models\User::where('email', 'acme.owner@example.test')->firstOrFail();
    $this->account = $this->user->currentAccount;
});

it('dispatches ExtractAppFeaturesJob and flags features_pending when features_json is null', function () {
    Queue::fake();
    $mine = ShopifyApp::factory()->create(['features_json' => null]);
    $comp = ShopifyApp::factory()->withFeatures()->create();

    AccountFollowedApp::factory()->mine($this->account)->create(['shopify_app_id' => $mine->id]);
    AccountFollowedApp::factory()->competitor($this->account)->create(['shopify_app_id' => $comp->id]);

    $response = $this->actingAs($this->user)->get('/customer/my-apps/versus?mine='.$mine->id.'&competitors[]='.$comp->id);
    $response->assertOk();

    Queue::assertPushed(ExtractAppFeaturesJob::class, fn ($job) => $job->appId === $mine->id);
    $props = $response->viewData('page')['props']['versus'];
    expect($props['columns'][0]['features_pending'])->toBeTrue();
    expect($props['columns'][1]['features_pending'])->toBeFalse();
});
