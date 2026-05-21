<?php

use App\Enums\AccountStatus;

it('exposes all expected cases with correct DB string values', function () {
    expect(AccountStatus::Trial->value)->toBe('trial');
    expect(AccountStatus::Active->value)->toBe('active');
    expect(AccountStatus::PastDue->value)->toBe('past_due');
    expect(AccountStatus::Suspended->value)->toBe('suspended');
    expect(AccountStatus::Cancelled->value)->toBe('cancelled');

    expect(count(AccountStatus::cases()))->toBe(5);
});
