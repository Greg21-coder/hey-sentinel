<?php

use App\Contracts\BatchLlmClient;
use App\Enums\AiStatus;
use App\Jobs\Ai\CompileBatchJob;
use App\Jobs\Ai\IngestBatchResultsJob;
use App\Jobs\Ai\PollBatchesJob;
use App\Models\AiBatch;
use App\Models\AiPainPoint;
use App\Models\ShopifyApp;
use App\Models\StoreReview;
use App\Services\Ai\FakeBatchLlmClient;
use App\Support\Ai\PainPointExtractionPrompt;
use Database\Seeders\AiPainPointVocabularySeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    PainPointExtractionPrompt::refreshVocabulary();
    $this->seed(AiPainPointVocabularySeeder::class);

    $this->fake = new FakeBatchLlmClient();
    $this->app->instance(BatchLlmClient::class, $this->fake);
});

it('compiles a batch from pending reviews and flips their status to batched', function () {
    $app = ShopifyApp::factory()->create();
    foreach (range(1, 3) as $i) {
        StoreReview::create([
            'shopify_app_id' => $app->id,
            'reviewer_name' => "Reviewer {$i}",
            'rating' => 2,
            'review_text' => "The pricing went up suddenly and support never replied. {$i}",
            'review_text_hash' => hash('sha256', "review-{$i}"),
            'ai_status' => AiStatus::Pending->value,
            'published_at' => now()->subDays($i),
        ]);
    }

    (new CompileBatchJob())->handle($this->fake);

    expect($this->fake->submissions)->toHaveCount(1);
    expect($this->fake->submissions[0]['requests'])->toHaveCount(3);

    $batch = AiBatch::first();
    expect($batch)->not->toBeNull();
    expect($batch->batch_id)->toBe('fake-batch-0001');
    expect($batch->request_count)->toBe(3);

    $statuses = StoreReview::where('shopify_app_id', $app->id)->pluck('ai_status');
    expect($statuses->every(fn ($s) => $s === AiStatus::Batched))->toBeTrue();
});

it('CompileBatchJob declares unique-dispatch protection to prevent the cron/manual race', function () {
    // Without ShouldBeUnique, two parallel dispatches (e.g. hourly cron + the
    // Filament "Reprocess AI" button) BOTH SELECT pending reviews before either
    // commits the status flip, submitting overlapping batches to the LLM provider
    // and double-billing. Observed in production data: pairs of ai_batches rows
    // submitted seconds apart with identical completion timestamps.
    $job = new CompileBatchJob();

    expect($job)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldBeUnique::class);
    expect($job->uniqueId())->toBe('compile-batch');
    // uniqueFor must match $timeout so the lock outlives the worst-case sync
    // Ollama batch (otherwise a long-running job releases its lock and a
    // duplicate dispatch can race in).
    expect($job->uniqueFor)->toBe($job->timeout);
});

it('polls open batches, marks completed, and dispatches ingest', function () {
    $batch = AiBatch::create([
        'provider' => 'anthropic',
        'batch_id' => 'fake-batch-XYZ',
        'status' => 'submitted',
        'request_count' => 1,
        'prompt_version' => 'v1',
        'submitted_at' => now(),
    ]);

    $this->fake->pollStatuses['fake-batch-XYZ'] = 'completed';
    \Illuminate\Support\Facades\Bus::fake([IngestBatchResultsJob::class]);

    (new PollBatchesJob())->handle($this->fake);

    expect($batch->fresh()->status)->toBe('completed');
    \Illuminate\Support\Facades\Bus::assertDispatched(
        IngestBatchResultsJob::class,
        fn ($job) => $job->aiBatchId === $batch->id
    );
});

it('ingests results: persists sentiment, pain points, drops unknown slugs', function () {
    $app = ShopifyApp::factory()->create();
    $review = StoreReview::create([
        'shopify_app_id' => $app->id,
        'reviewer_name' => 'Test Store',
        'rating' => 1,
        'review_text' => 'Pricing too high and support never replies.',
        'review_text_hash' => hash('sha256', 'ingest-test'),
        'ai_status' => AiStatus::Batched->value,
        'published_at' => now(),
    ]);

    $batch = AiBatch::create([
        'provider' => 'anthropic',
        'batch_id' => 'fake-batch-INGEST',
        'status' => 'completed',
        'request_count' => 1,
        'prompt_version' => 'v1',
        'submitted_at' => now()->subHour(),
        'completed_at' => now(),
    ]);

    $this->fake->resultsByBatchId['fake-batch-INGEST'] = [[
        'custom_id' => "review-{$review->id}",
        'result' => [
            'type' => 'succeeded',
            'message' => [
                'content' => [[
                    'type' => 'text',
                    'text' => json_encode([
                        'sentiment' => 'negative',
                        'pain_points' => [
                            ['slug' => 'pricing-too-high', 'severity' => 'high', 'confidence' => 0.95],
                            ['slug' => 'slow-support-response', 'severity' => 'medium', 'confidence' => 0.8],
                            ['slug' => 'totally-made-up-slug', 'severity' => 'low', 'confidence' => 0.5],
                        ],
                    ]),
                ]],
            ],
        ],
    ]];

    (new IngestBatchResultsJob($batch->id))->handle($this->fake);

    $fresh = $review->fresh();
    expect($fresh->ai_status)->toBe(AiStatus::Processed);
    expect($fresh->ai_sentiment)->toBe('negative');
    expect($fresh->ai_processed_at)->not->toBeNull();

    $linked = DB::table('review_pain_point')->where('review_id', $review->id)->get();
    expect($linked)->toHaveCount(2); // unknown slug dropped
    expect($linked->pluck('pain_point_id')->all())
        ->toEqualCanonicalizing(AiPainPoint::whereIn('slug', ['pricing-too-high', 'slow-support-response'])->pluck('id')->all());
});

it('stamps the active llm provider on the batch row', function () {
    config(['ai.provider' => 'ollama']);

    $app = ShopifyApp::factory()->create();
    StoreReview::create([
        'shopify_app_id' => $app->id,
        'reviewer_name' => 'X',
        'rating' => 1,
        'review_text' => 'whatever',
        'review_text_hash' => hash('sha256', 'provider-test'),
        'ai_status' => AiStatus::Pending->value,
        'published_at' => now(),
    ]);

    (new CompileBatchJob())->handle($this->fake);

    expect(AiBatch::first()->provider)->toBe('ollama');
});

it('marks review as error when LLM returns JSON missing required keys', function () {
    $app = ShopifyApp::factory()->create();
    $review = StoreReview::create([
        'shopify_app_id' => $app->id,
        'reviewer_name' => 'Store',
        'rating' => 1,
        'review_text' => 'real complaint about something not in vocabulary',
        'review_text_hash' => hash('sha256', 'malformed-test'),
        'ai_status' => AiStatus::Batched->value,
        'published_at' => now(),
    ]);
    $batch = AiBatch::create([
        'provider' => 'ollama',
        'batch_id' => 'fake-batch-MALFORMED',
        'status' => 'completed',
        'request_count' => 1,
        'prompt_version' => 'v1',
        'submitted_at' => now()->subHour(),
        'completed_at' => now(),
    ]);

    $this->fake->resultsByBatchId['fake-batch-MALFORMED'] = [[
        'custom_id' => "review-{$review->id}",
        'result' => [
            'type' => 'succeeded',
            'message' => [
                'content' => [['type' => 'text', 'text' => '{}']],
            ],
        ],
    ]];

    (new IngestBatchResultsJob($batch->id))->handle($this->fake);

    expect($review->fresh()->ai_status)->toBe(AiStatus::Error);
});

it('processes review when LLM returns valid shape with explicitly empty pain points', function () {
    $app = ShopifyApp::factory()->create();
    $review = StoreReview::create([
        'shopify_app_id' => $app->id,
        'reviewer_name' => 'Store',
        'rating' => 5,
        'review_text' => 'Love this app, no complaints.',
        'review_text_hash' => hash('sha256', 'empty-pp-test'),
        'ai_status' => AiStatus::Batched->value,
        'published_at' => now(),
    ]);
    $batch = AiBatch::create([
        'provider' => 'ollama',
        'batch_id' => 'fake-batch-EMPTY-PP',
        'status' => 'completed',
        'request_count' => 1,
        'prompt_version' => 'v1',
        'submitted_at' => now()->subHour(),
        'completed_at' => now(),
    ]);

    $this->fake->resultsByBatchId['fake-batch-EMPTY-PP'] = [[
        'custom_id' => "review-{$review->id}",
        'result' => [
            'type' => 'succeeded',
            'message' => [
                'content' => [['type' => 'text', 'text' => json_encode([
                    'sentiment' => 'positive',
                    'pain_points' => [],
                ])]],
            ],
        ],
    ]];

    (new IngestBatchResultsJob($batch->id))->handle($this->fake);

    $fresh = $review->fresh();
    expect($fresh->ai_status)->toBe(AiStatus::Processed);
    expect($fresh->ai_sentiment)->toBe('positive');
});

it('marks review ai_status=error when result type is errored', function () {
    $app = ShopifyApp::factory()->create();
    $review = StoreReview::create([
        'shopify_app_id' => $app->id,
        'reviewer_name' => 'Store',
        'rating' => 3,
        'review_text' => 'whatever',
        'review_text_hash' => hash('sha256', 'err-test'),
        'ai_status' => AiStatus::Batched->value,
        'published_at' => now(),
    ]);
    $batch = AiBatch::create([
        'provider' => 'anthropic',
        'batch_id' => 'fake-batch-ERR',
        'status' => 'completed',
        'request_count' => 1,
        'prompt_version' => 'v1',
        'submitted_at' => now()->subHour(),
        'completed_at' => now(),
    ]);

    $this->fake->resultsByBatchId['fake-batch-ERR'] = [[
        'custom_id' => "review-{$review->id}",
        'result' => ['type' => 'errored', 'error' => ['message' => 'rate_limit']],
    ]];

    (new IngestBatchResultsJob($batch->id))->handle($this->fake);

    expect($review->fresh()->ai_status)->toBe(AiStatus::Error);
});
