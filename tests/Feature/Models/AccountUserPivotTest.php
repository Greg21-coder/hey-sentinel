<?php

use App\Enums\UserRole;
use App\Models\Account;
use App\Models\AccountUser;
use App\Models\User;

it('pivot row has surrogate id, casts role enum, and stores invitation metadata', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create();
    $inviter = User::factory()->create();

    $account->users()->attach($user->id, [
        'role' => UserRole::Admin->value,
        'invited_by' => $inviter->id,
        'invitation_accepted_at' => now(),
    ]);

    $pivot = AccountUser::query()
        ->where('account_id', $account->id)
        ->where('user_id', $user->id)
        ->first();

    expect($pivot)->not->toBeNull();
    expect($pivot->id)->toBeGreaterThan(0);
    expect($pivot->role)->toBe(UserRole::Admin);
    expect($pivot->invited_by)->toBe($inviter->id);
    expect($pivot->invitation_accepted_at)->not->toBeNull();
});
