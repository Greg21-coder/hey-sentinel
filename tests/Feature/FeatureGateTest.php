<?php

use App\Enums\FeatureValueType;
use App\Models\Account;
use App\Models\FeatureUsageLog;
use App\Models\Plan;
use App\Models\PlanFeature;

it('canUse returns true for a boolean feature set to true on the plan', function () {
    $plan = Plan::factory()->premium()->create();
    PlanFeature::factory()->for($plan)->create([
        'feature_key' => 'export_csv',
        'feature_value' => 'true',
        'value_type' => FeatureValueType::Boolean->value,
    ]);

    $account = Account::factory()->for($plan)->create();

    expect($account->canUse('export_csv'))->toBeTrue();
});

it('canUse returns false for a boolean feature set to false', function () {
    $plan = Plan::factory()->free()->create();
    PlanFeature::factory()->for($plan)->create([
        'feature_key' => 'export_csv',
        'feature_value' => 'false',
        'value_type' => FeatureValueType::Boolean->value,
    ]);

    $account = Account::factory()->for($plan)->create();

    expect($account->canUse('export_csv'))->toBeFalse();
});

it('canUse respects integer limits via usageThisPeriod', function () {
    $plan = Plan::factory()->free()->create();
    PlanFeature::factory()->for($plan)->create([
        'feature_key' => 'saved_searches',
        'feature_value' => '3',
        'value_type' => FeatureValueType::Integer->value,
    ]);

    $account = Account::factory()->for($plan)->create();

    expect($account->canUse('saved_searches'))->toBeTrue();
    FeatureUsageLog::factory()->for($account)->forFeature('saved_searches', 1)->count(2)->create();
    expect($account->canUse('saved_searches'))->toBeTrue();
    FeatureUsageLog::factory()->for($account)->forFeature('saved_searches', 1)->create();
    expect($account->canUse('saved_searches'))->toBeFalse();
});
