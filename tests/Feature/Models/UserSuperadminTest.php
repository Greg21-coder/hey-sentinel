<?php

use App\Enums\UserRole;
use App\Models\Account;
use App\Models\AccountUser;
use App\Models\Plan;
use App\Models\User;

beforeEach(function () {
    $this->seed(\Database\Seeders\PlanSeeder::class);
});

it('isSuperAdmin returns true for members of heysentinel-internal account', function () {
    $internal = Account::factory()->create(['slug' => 'heysentinel-internal']);
    $user = User::factory()->create();
    AccountUser::create([
        'account_id' => $internal->id,
        'user_id' => $user->id,
        'role' => UserRole::Member->value,
        'invitation_accepted_at' => now(),
    ]);

    expect($user->isSuperAdmin())->toBeTrue();
});

it('isSuperAdmin returns false for users only in other accounts', function () {
    Account::factory()->create(['slug' => 'heysentinel-internal']);
    $other = Account::factory()->create(['slug' => 'acme-corp']);
    $user = User::factory()->create();
    AccountUser::create([
        'account_id' => $other->id,
        'user_id' => $user->id,
        'role' => UserRole::Owner->value,
        'invitation_accepted_at' => now(),
    ]);

    expect($user->isSuperAdmin())->toBeFalse();
});

it('currentAccount returns the first owned account when present', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['owner_user_id' => $user->id]);

    expect($user->currentAccount?->id)->toBe($account->id);
});

it('currentAccount falls back to the first membership account', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    AccountUser::create([
        'account_id' => $account->id,
        'user_id' => $user->id,
        'role' => UserRole::Member->value,
        'invitation_accepted_at' => now(),
    ]);

    expect($user->currentAccount?->id)->toBe($account->id);
});

it('currentAccount returns null for users with no accounts', function () {
    $user = User::factory()->create();
    expect($user->currentAccount)->toBeNull();
});

it('non-superadmin cannot access the admin panel', function () {
    $user = User::factory()->create();
    $panel = \Filament\Facades\Filament::getPanel('admin');

    expect($user->canAccessPanel($panel))->toBeFalse();
});

it('superadmin can access the admin panel', function () {
    Account::factory()->create(['slug' => 'heysentinel-internal']);
    $user = User::factory()->superadmin()->create();
    $panel = \Filament\Facades\Filament::getPanel('admin');

    expect($user->canAccessPanel($panel))->toBeTrue();
});

it('any user can access the customer panel', function () {
    $user = User::factory()->create();
    $panel = \Filament\Facades\Filament::getPanel('customer');

    expect($user->canAccessPanel($panel))->toBeTrue();
})->skip('panel not registered yet — Task 5');
