<?php

use App\Jobs\Ai\ExtractAppFeaturesJob;
use App\Models\ShopifyApp;
use App\Services\Ai\AppFeatureExtractionService;

it('skips when features were extracted within the last 30 days with current model', function () {
    config()->set('ai.ollama.model', 'llama3.1:8b');
    $app = ShopifyApp::factory()->create([
        'features_extracted_at' => now()->subDays(10),
        'features_extraction_model' => 'llama3.1:8b',
        'features_json' => [['name' => 'X', 'category' => 'Other', 'confidence' => 0.9, 'source' => 'description']],
    ]);

    $service = $this->mock(AppFeatureExtractionService::class);
    $service->shouldNotReceive('extract');

    (new ExtractAppFeaturesJob($app->id))->handle($service);
});

it('runs extraction when features_extracted_at is null', function () {
    config()->set('ai.ollama.model', 'llama3.1:8b');
    $app = ShopifyApp::factory()->create(['features_extracted_at' => null, 'features_json' => null]);

    $service = $this->mock(AppFeatureExtractionService::class);
    $service->shouldReceive('extract')->once();

    (new ExtractAppFeaturesJob($app->id))->handle($service);
});

it('runs extraction when model has changed', function () {
    config()->set('ai.ollama.model', 'llama3.1:8b');
    $app = ShopifyApp::factory()->create([
        'features_extracted_at' => now()->subDay(),
        'features_extraction_model' => 'qwen2.5:7b',
        'features_json' => [['name' => 'X', 'category' => 'Other', 'confidence' => 0.9, 'source' => 'description']],
    ]);

    $service = $this->mock(AppFeatureExtractionService::class);
    $service->shouldReceive('extract')->once();

    (new ExtractAppFeaturesJob($app->id))->handle($service);
});

it('on failed callback writes empty features_json and timestamp', function () {
    $app = ShopifyApp::factory()->create(['features_extracted_at' => null, 'features_json' => null]);

    $job = new ExtractAppFeaturesJob($app->id);
    $job->failed(new RuntimeException('boom'));

    $fresh = $app->fresh();
    expect($fresh->features_json)->toBe([]);
    expect($fresh->features_extracted_at)->not->toBeNull();
});
