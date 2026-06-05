<?php

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

use App\Models\Account;
use App\Models\AppComparisonSummary;
use App\Models\ShopifyApp;
use App\Models\ShopifyAppCategory;
use App\Services\Versus\ComparisonAssemblerService;

beforeEach(function () {
    $this->account = Account::factory()->create();
    $this->category = ShopifyAppCategory::create(['slug' => 'marketing', 'name' => 'Marketing']);
});

it('marks no winner on rating tie within 0.1', function () {
    $mine = ShopifyApp::factory()->create(['category_id' => $this->category->id, 'average_rating' => 4.50, 'total_reviews' => 100]);
    $comp = ShopifyApp::factory()->create(['category_id' => $this->category->id, 'average_rating' => 4.55, 'total_reviews' => 100]);

    $payload = (new ComparisonAssemblerService)->assemble($mine, collect([$comp]), $this->account->id);

    $ratingRow = collect($payload['rows'])->firstWhere('metric', 'rating');
    expect($ratingRow['winner_index'])->toBeNull();
});

it('picks mine as rating winner when diff >= 0.1', function () {
    $mine = ShopifyApp::factory()->create(['category_id' => $this->category->id, 'average_rating' => 4.80, 'total_reviews' => 100]);
    $comp = ShopifyApp::factory()->create(['category_id' => $this->category->id, 'average_rating' => 4.50, 'total_reviews' => 100]);

    $payload = (new ComparisonAssemblerService)->assemble($mine, collect([$comp]), $this->account->id);

    $row = collect($payload['rows'])->firstWhere('metric', 'rating');
    expect($row['winner_index'])->toBe(0);
});

it('free pricing beats any paid', function () {
    $mine = ShopifyApp::factory()->create(['category_id' => $this->category->id, 'pricing_has_free' => true, 'pricing_min_usd' => 0]);
    $comp = ShopifyApp::factory()->create(['category_id' => $this->category->id, 'pricing_has_free' => false, 'pricing_min_usd' => 10]);

    $payload = (new ComparisonAssemblerService)->assemble($mine, collect([$comp]), $this->account->id);

    $row = collect($payload['rows'])->firstWhere('metric', 'pricing');
    expect($row['winner_index'])->toBe(0);
});

it('flags category_mismatch per column', function () {
    $mine = ShopifyApp::factory()->create(['category_id' => $this->category->id]);
    $other = ShopifyAppCategory::create(['slug' => 'shipping', 'name' => 'Shipping']);
    $comp = ShopifyApp::factory()->create(['category_id' => $other->id]);

    $payload = (new ComparisonAssemblerService)->assemble($mine, collect([$comp]), $this->account->id);

    expect($payload['columns'][0]['category_mismatch'])->toBeFalse();
    expect($payload['columns'][1]['category_mismatch'])->toBeTrue();
});

it('includes cached_summary when one exists for the combination', function () {
    $mine = ShopifyApp::factory()->create(['category_id' => $this->category->id]);
    $comp = ShopifyApp::factory()->create(['category_id' => $this->category->id]);

    AppComparisonSummary::create([
        'account_id' => $this->account->id,
        'mine_shopify_app_id' => $mine->id,
        'competitor_ids_hash' => AppComparisonSummary::hashFor([$comp->id]),
        'competitor_ids' => [$comp->id],
        'summary' => 'mine is better',
        'winner_shopify_app_id' => $mine->id,
        'winner_reasoning' => 'higher rating',
        'per_metric_comments' => ['rating' => 'great'],
        'model' => 'llama3.1:8b',
        'prompt_version' => 'v1',
        'generated_at' => now(),
    ]);

    $payload = (new ComparisonAssemblerService)->assemble($mine, collect([$comp]), $this->account->id);

    expect($payload['cached_summary'])->not->toBeNull();
    expect($payload['cached_summary']['summary'])->toBe('mine is better');
});
