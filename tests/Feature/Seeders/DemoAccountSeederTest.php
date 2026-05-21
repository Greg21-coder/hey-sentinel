<?php

use App\Models\Account;
use App\Models\AccountUser;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

it('seeder produces the expected demo state and is idempotent', function () {
    Artisan::call('db:seed', ['--force' => true]);

    expect(Plan::count())->toBe(3);
    expect(PlanFeature::count())->toBe(18);
    expect(User::count())->toBe(13);
    expect(Account::count())->toBe(5);
    expect(AccountUser::count())->toBe(15);

    expect(User::where('email', 'admin@heysentinel.test')->exists())->toBeTrue();
    expect(Account::where('slug', 'heysentinel-internal')->exists())->toBeTrue();

    $cross = User::where('email', 'cross.1@example.test')->firstOrFail();
    expect($cross->accounts()->count())->toBe(2);

    // Run again — counts must be identical
    Artisan::call('db:seed', ['--force' => true]);

    expect(Plan::count())->toBe(3);
    expect(User::count())->toBe(13);
    expect(Account::count())->toBe(5);
    expect(AccountUser::count())->toBe(15);
});
