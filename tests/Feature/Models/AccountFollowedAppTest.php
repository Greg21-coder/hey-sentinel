<?php

use App\Enums\FollowedAppKind;
use App\Models\Account;
use App\Models\AccountFollowedApp;
use App\Models\Concerns\BelongsToAccount;
use App\Models\ShopifyApp;
use App\Models\User;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->seed(\Database\Seeders\PlanSeeder::class);
});

it('stores account_id, shopify_app_id, kind, followed_at, notes', function () {
    $account = Account::factory()->create();
    $app = ShopifyApp::factory()->create();

    $follow = AccountFollowedApp::create([
        'account_id' => $account->id,
        'shopify_app_id' => $app->id,
        'kind' => FollowedAppKind::Mine->value,
        'followed_at' => now(),
        'notes' => 'My flagship app',
    ]);

    expect($follow->fresh())
        ->kind->toBe(FollowedAppKind::Mine)
        ->notes->toBe('My flagship app')
        ->account_id->toBe($account->id)
        ->shopify_app_id->toBe($app->id);
});

it('enforces unique(account_id, shopify_app_id)', function () {
    $account = Account::factory()->create();
    $app = ShopifyApp::factory()->create();

    AccountFollowedApp::create([
        'account_id' => $account->id,
        'shopify_app_id' => $app->id,
        'kind' => FollowedAppKind::Mine->value,
        'followed_at' => now(),
    ]);

    expect(fn () => AccountFollowedApp::create([
        'account_id' => $account->id,
        'shopify_app_id' => $app->id,
        'kind' => FollowedAppKind::Competitor->value,
        'followed_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('Account hasMany followedApps and apps via belongsToMany', function () {
    $account = Account::factory()->create();
    $apps = ShopifyApp::factory()->count(3)->create();

    BelongsToAccount::disable();
    foreach ($apps as $app) {
        AccountFollowedApp::create([
            'account_id' => $account->id,
            'shopify_app_id' => $app->id,
            'kind' => FollowedAppKind::Mine->value,
            'followed_at' => now(),
        ]);
    }
    BelongsToAccount::enable();

    expect($account->fresh()->followedApps)->toHaveCount(3);
    expect($account->fresh()->apps)->toHaveCount(3);
});

it('BelongsToAccount scopes followedApps to the auth user current account', function () {
    $userA = User::factory()->create();
    $accountA = Account::factory()->create(['owner_user_id' => $userA->id]);
    $userB = User::factory()->create();
    $accountB = Account::factory()->create(['owner_user_id' => $userB->id]);
    $app = ShopifyApp::factory()->create();

    BelongsToAccount::disable();
    AccountFollowedApp::create([
        'account_id' => $accountA->id,
        'shopify_app_id' => $app->id,
        'kind' => FollowedAppKind::Mine->value,
        'followed_at' => now(),
    ]);
    AccountFollowedApp::create([
        'account_id' => $accountB->id,
        'shopify_app_id' => $app->id,
        'kind' => FollowedAppKind::Mine->value,
        'followed_at' => now(),
    ]);
    BelongsToAccount::enable();

    $this->actingAs($userA);
    expect(AccountFollowedApp::count())->toBe(1);
    expect(AccountFollowedApp::first()->account_id)->toBe($accountA->id);

    $this->actingAs($userB);
    expect(AccountFollowedApp::count())->toBe(1);
    expect(AccountFollowedApp::first()->account_id)->toBe($accountB->id);
});
