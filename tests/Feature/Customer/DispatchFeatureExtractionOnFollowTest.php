<?php

use App\Events\AppFollowed;
use App\Jobs\Ai\ExtractAppFeaturesJob;
use App\Models\Account;
use App\Models\ShopifyApp;
use Illuminate\Support\Facades\Queue;

it('dispatches ExtractAppFeaturesJob when features_json is null', function () {
    Queue::fake();
    $account = Account::factory()->create();
    $app = ShopifyApp::factory()->create(['features_json' => null]);

    event(new AppFollowed($account, $app));

    Queue::assertPushed(ExtractAppFeaturesJob::class, fn ($job) => $job->appId === $app->id);
});

it('does not dispatch when features already extracted', function () {
    Queue::fake();
    $account = Account::factory()->create();
    $app = ShopifyApp::factory()->withFeatures()->create();

    event(new AppFollowed($account, $app));

    Queue::assertNotPushed(ExtractAppFeaturesJob::class);
});
