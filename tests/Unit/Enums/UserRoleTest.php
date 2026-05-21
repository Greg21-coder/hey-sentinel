<?php

use App\Enums\UserRole;

it('owner can manage billing, admin and member cannot', function () {
    expect(UserRole::Owner->canManageBilling())->toBeTrue();
    expect(UserRole::Admin->canManageBilling())->toBeFalse();
    expect(UserRole::Member->canManageBilling())->toBeFalse();
});

it('owner and admin can invite, member cannot', function () {
    expect(UserRole::Owner->canInvite())->toBeTrue();
    expect(UserRole::Admin->canInvite())->toBeTrue();
    expect(UserRole::Member->canInvite())->toBeFalse();
});

it('exposes the expected DB string values', function () {
    expect(UserRole::Owner->value)->toBe('owner');
    expect(UserRole::Admin->value)->toBe('admin');
    expect(UserRole::Member->value)->toBe('member');
});
