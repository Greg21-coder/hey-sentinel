<?php

use App\Enums\UserRole;
use App\Models\Account;
use App\Models\User;

it('a user can belong to multiple accounts with different roles', function () {
    $user = User::factory()->create();
    $acmeAccount = Account::factory()->create();
    $betaAccount = Account::factory()->create();

    $user->accounts()->attach($acmeAccount->id, [
        'role' => UserRole::Owner->value,
        'invitation_accepted_at' => now(),
    ]);
    $user->accounts()->attach($betaAccount->id, [
        'role' => UserRole::Member->value,
        'invitation_accepted_at' => now(),
    ]);

    expect($user->accounts()->count())->toBe(2);
    expect($user->roleIn($acmeAccount))->toBe(UserRole::Owner);
    expect($user->roleIn($betaAccount))->toBe(UserRole::Member);
});
