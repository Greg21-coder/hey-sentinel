<?php

use App\Exceptions\Ai\VersusSummaryFailed;
use App\Models\AccountFollowedApp;
use App\Models\AppComparisonSummary;
use App\Models\ShopifyApp;
use App\Models\ShopifyAppCategory;
use App\Services\Ai\VersusSummaryService;
use Database\Seeders\DemoAccountSeeder;
use Database\Seeders\PlanSeeder;

beforeEach(function () {
    $this->seed(PlanSeeder::class);
    $this->seed(DemoAccountSeeder::class);
    $this->user = \App\Models\User::where('email', 'acme.owner@example.test')->firstOrFail();
    $this->account = $this->user->currentAccount;
    $cat = ShopifyAppCategory::create(['slug' => 'm', 'name' => 'M']);

    $this->mine = ShopifyApp::factory()->withFeatures()->create(['category_id' => $cat->id]);
    $this->comp = ShopifyApp::factory()->withFeatures()->create(['category_id' => $cat->id]);
    AccountFollowedApp::factory()->mine($this->account)->create(['shopify_app_id' => $this->mine->id]);
    AccountFollowedApp::factory()->competitor($this->account)->create(['shopify_app_id' => $this->comp->id]);
});

it('creates a comparison summary row on first call', function () {
    $this->mock(VersusSummaryService::class)->shouldReceive('generate')->once()->andReturnUsing(
        fn ($accountId, $mine, $comps) => AppComparisonSummary::create([
            'account_id' => $accountId,
            'mine_shopify_app_id' => $mine->id,
            'competitor_ids_hash' => AppComparisonSummary::hashFor($comps->pluck('id')->all()),
            'competitor_ids' => $comps->pluck('id')->all(),
            'summary' => 'fresh',
            'model' => 'm',
            'prompt_version' => 'v',
            'generated_at' => now(),
        ])
    );

    $response = $this->actingAs($this->user)
        ->post('/customer/my-apps/versus/summary', ['mine' => $this->mine->id, 'competitors' => [$this->comp->id]]);

    $response->assertRedirect();
    expect(AppComparisonSummary::count())->toBe(1);
});

it('returns 429 on second call within 30 seconds', function () {
    $this->mock(VersusSummaryService::class)->shouldReceive('generate')->once()->andReturnNull();

    $this->actingAs($this->user)->post('/customer/my-apps/versus/summary', ['mine' => $this->mine->id, 'competitors' => [$this->comp->id]]);
    $response = $this->actingAs($this->user)->post('/customer/my-apps/versus/summary', ['mine' => $this->mine->id, 'competitors' => [$this->comp->id]]);
    $response->assertStatus(429);
});

it('returns 503 and does not modify cache on Ollama failure', function () {
    $hash = AppComparisonSummary::hashFor([$this->comp->id]);
    AppComparisonSummary::create([
        'account_id' => $this->account->id,
        'mine_shopify_app_id' => $this->mine->id,
        'competitor_ids_hash' => $hash,
        'competitor_ids' => [$this->comp->id],
        'summary' => 'old',
        'model' => 'old',
        'prompt_version' => 'old',
        'generated_at' => now()->subHour(),
    ]);

    $this->mock(VersusSummaryService::class)
        ->shouldReceive('generate')->once()->andThrow(new VersusSummaryFailed('boom'));

    $response = $this->actingAs($this->user)
        ->post('/customer/my-apps/versus/summary', ['mine' => $this->mine->id, 'competitors' => [$this->comp->id]]);

    $response->assertStatus(503);
    expect(AppComparisonSummary::first()->summary)->toBe('old');
});
