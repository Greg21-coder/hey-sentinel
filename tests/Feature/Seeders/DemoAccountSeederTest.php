<?php

use App\Models\Account;
use App\Models\AccountFollowedApp;
use App\Models\AccountUser;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\ShopifyApp;
use App\Models\StoreReview;
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

    // Demo data invariants: the default seed must leave the customer panel
    // demonstrable — apps, reviews, and followed-app pivots present. A prior
    // refactor moved fake-app generation out of CoreDataDemoSeeder and broke
    // this silently; CustomerDemoSeeder logged "attached 0 apps" and CI did
    // not catch it. Assert the floor so the regression cannot recur.
    expect(ShopifyApp::count())->toBeGreaterThan(0);
    expect(StoreReview::count())->toBeGreaterThan(0);
    AccountFollowedApp::disable();
    expect(AccountFollowedApp::count())->toBeGreaterThan(0);
    AccountFollowedApp::enable();

    // Run again — counts must be identical
    Artisan::call('db:seed', ['--force' => true]);

    expect(Plan::count())->toBe(3);
    expect(User::count())->toBe(13);
    expect(Account::count())->toBe(5);
    expect(AccountUser::count())->toBe(15);
});
