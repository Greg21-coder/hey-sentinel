<?php

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

use App\Models\Account;
use App\Models\AppComparisonSummary;
use App\Models\ShopifyApp;
use App\Models\ShopifyAppCategory;
use App\Services\Ai\VersusSummaryService;
use App\Services\Versus\ComparisonAssemblerService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('ai.ollama.base_url', 'http://ollama.test');
    config()->set('ai.ollama.model', 'llama3.1:8b');
    config()->set('ai.ollama.request_timeout', 30);

    $this->account = Account::factory()->create();
    $cat = ShopifyAppCategory::create(['slug' => 'm', 'name' => 'M']);
    $this->mine = ShopifyApp::factory()->withFeatures()->create(['category_id' => $cat->id]);
    $this->comp = ShopifyApp::factory()->withFeatures()->create(['category_id' => $cat->id]);
});

it('persists a new row via updateOrCreate on success', function () {
    Http::fake(['ollama.test/*' => Http::response([
        'message' => ['content' => json_encode([
            'winner_app_id' => $this->mine->id,
            'winner_reasoning' => 'mine wins on rating',
            'per_metric_comments' => ['rating' => 'higher', 'pricing' => 'free tier'],
        ])],
    ])]);

    $payload = (new ComparisonAssemblerService)->assemble($this->mine, collect([$this->comp]), $this->account->id);
    (new VersusSummaryService)->generate($this->account->id, $this->mine, collect([$this->comp]), $payload);

    $row = AppComparisonSummary::where('account_id', $this->account->id)->where('mine_shopify_app_id', $this->mine->id)->first();
    expect($row)->not->toBeNull();
    expect($row->winner_shopify_app_id)->toBe($this->mine->id);
    expect($row->prompt_version)->toBe('v1-versus-2026-06');
});

it('replaces existing row when called again for the same combination', function () {
    $hash = AppComparisonSummary::hashFor([$this->comp->id]);
    AppComparisonSummary::create([
        'account_id' => $this->account->id,
        'mine_shopify_app_id' => $this->mine->id,
        'competitor_ids_hash' => $hash,
        'competitor_ids' => [$this->comp->id],
        'summary' => 'old',
        'model' => 'old',
        'prompt_version' => 'old',
        'generated_at' => now(),
    ]);

    Http::fake(['ollama.test/*' => Http::response([
        'message' => ['content' => json_encode([
            'winner_app_id' => null,
            'winner_reasoning' => 'inconclusive',
            'per_metric_comments' => [],
        ])],
    ])]);

    $payload = (new ComparisonAssemblerService)->assemble($this->mine, collect([$this->comp]), $this->account->id);
    (new VersusSummaryService)->generate($this->account->id, $this->mine, collect([$this->comp]), $payload);

    expect(AppComparisonSummary::count())->toBe(1);
    expect(AppComparisonSummary::first()->summary)->toBe('inconclusive');
});

it('throws on unparseable JSON', function () {
    Http::fake(['ollama.test/*' => Http::response(['message' => ['content' => 'not json']])]);
    $payload = (new ComparisonAssemblerService)->assemble($this->mine, collect([$this->comp]), $this->account->id);

    expect(fn () => (new VersusSummaryService)->generate($this->account->id, $this->mine, collect([$this->comp]), $payload))
        ->toThrow(\App\Exceptions\Ai\VersusSummaryFailed::class);
});

it('drops winner_app_id when not in the comparison set', function () {
    Http::fake(['ollama.test/*' => Http::response([
        'message' => ['content' => json_encode([
            'winner_app_id' => 99999,
            'winner_reasoning' => 'mine wins',
            'per_metric_comments' => [],
        ])],
    ])]);

    $payload = (new ComparisonAssemblerService)->assemble($this->mine, collect([$this->comp]), $this->account->id);
    (new VersusSummaryService)->generate($this->account->id, $this->mine, collect([$this->comp]), $payload);

    expect(AppComparisonSummary::first()->winner_shopify_app_id)->toBeNull();
});
