<?php

use App\Exceptions\Ai\VersusSummaryFailed;
use App\Jobs\Ai\GenerateVersusSummaryJob;
use App\Models\Account;
use App\Models\AppComparisonSummary;
use App\Models\ShopifyApp;
use App\Models\ShopifyAppCategory;
use App\Services\Ai\VersusSummaryService;
use App\Services\Versus\ComparisonAssemblerService;

beforeEach(function () {
    $this->account = Account::factory()->create();
    $cat = ShopifyAppCategory::create(['slug' => 'm', 'name' => 'M']);
    $this->mine = ShopifyApp::factory()->withFeatures()->create(['category_id' => $cat->id]);
    $this->comp = ShopifyApp::factory()->withFeatures()->create(['category_id' => $cat->id]);
});

it('failure does not modify any prior cached row', function () {
    AppComparisonSummary::create([
        'account_id' => $this->account->id,
        'mine_shopify_app_id' => $this->mine->id,
        'competitor_ids_hash' => AppComparisonSummary::hashFor([$this->comp->id]),
        'competitor_ids' => [$this->comp->id],
        'summary' => 'cached',
        'model' => 'old',
        'prompt_version' => 'old',
        'generated_at' => now()->subHour(),
    ]);

    $service = $this->mock(VersusSummaryService::class);
    $service->shouldReceive('generate')->once()->andThrow(new VersusSummaryFailed('boom'));

    $payload = (new ComparisonAssemblerService)->assemble($this->mine, collect([$this->comp]), $this->account->id);

    try {
        (new GenerateVersusSummaryJob($this->account->id, $this->mine->id, [$this->comp->id], $payload))
            ->handle($service);
    } catch (VersusSummaryFailed) {
        // expected
    }

    expect(AppComparisonSummary::first()->summary)->toBe('cached');
});
