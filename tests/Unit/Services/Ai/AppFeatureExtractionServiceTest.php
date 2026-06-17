<?php

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

use App\Enums\AiStatus;
use App\Models\ShopifyApp;
use App\Models\StoreReview;
use App\Services\Ai\AppFeatureExtractionService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('ai.ollama.base_url', 'http://ollama.test');
    config()->set('ai.ollama.model', 'llama3.1:8b');
    config()->set('ai.ollama.request_timeout', 30);
});

it('returns empty and skips persistence when fewer than 3 processed reviews', function () {
    $app = ShopifyApp::factory()->create(['total_reviews' => 1]);
    StoreReview::factory()->create([
        'shopify_app_id' => $app->id,
        'ai_status' => AiStatus::Processed->value,
    ]);

    Http::fake();
    (new AppFeatureExtractionService)->extract($app->fresh());

    expect($app->fresh()->features_json)->toBeNull();
    Http::assertNothingSent();
});

it('persists features on a valid JSON response', function () {
    $app = ShopifyApp::factory()->create(['total_reviews' => 100, 'description' => 'Great app']);
    StoreReview::factory()->count(5)->create([
        'shopify_app_id' => $app->id,
        'ai_status' => AiStatus::Processed->value,
    ]);

    Http::fake([
        'ollama.test/*' => Http::response([
            'message' => ['content' => json_encode([
                'features' => [
                    ['name' => 'A/B testing', 'category' => 'Analytics', 'confidence' => 0.9],
                    ['name' => 'Klaviyo integration', 'category' => 'Integrations', 'confidence' => 0.85],
                ],
            ])],
        ]),
    ]);

    (new AppFeatureExtractionService)->extract($app->fresh());

    $fresh = $app->fresh();
    expect($fresh->features_json)->toHaveCount(2);
    expect($fresh->features_json[0]['name'])->toBe('A/B testing');
    expect($fresh->features_extraction_model)->toBe('llama3.1:8b');
    expect($fresh->features_extracted_at)->not->toBeNull();
});

it('throws FeatureExtractionFailed on unparseable JSON', function () {
    $app = ShopifyApp::factory()->create(['total_reviews' => 100]);
    StoreReview::factory()->count(5)->create([
        'shopify_app_id' => $app->id,
        'ai_status' => AiStatus::Processed->value,
    ]);

    Http::fake([
        'ollama.test/*' => Http::response(['message' => ['content' => 'not json']]),
    ]);

    expect(fn () => (new AppFeatureExtractionService)->extract($app->fresh()))
        ->toThrow(\App\Exceptions\Ai\FeatureExtractionFailed::class);
});
