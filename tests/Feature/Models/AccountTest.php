<?php

use App\Models\Account;
use App\Models\Plan;
use App\Models\User;

it('auto-generates a uuid on create', function () {
    $account = Account::factory()->create();

    expect($account->uuid)
        ->not->toBeNull()
        ->and($account->uuid)
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

it('belongs to a plan', function () {
    $plan = Plan::factory()->premium()->create();
    $account = Account::factory()->for($plan)->create();

    expect($account->plan)->toBeInstanceOf(Plan::class);
    expect($account->plan->slug)->toBe('premium');
});

it('returns the owner via the owner relation', function () {
    $owner = User::factory()->create(['email' => 'owner@example.test']);
    $account = Account::factory()->create(['owner_user_id' => $owner->id]);

    expect($account->owner)->toBeInstanceOf(User::class);
    expect($account->owner->email)->toBe('owner@example.test');
});
