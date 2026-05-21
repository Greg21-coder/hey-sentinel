<?php

use App\Enums\FeatureValueType;
use App\Enums\PlanStatus;
use App\Models\Plan;
use App\Models\PlanFeature;

it('has many features and casts their values correctly per value_type', function () {
    $plan = Plan::factory()->create();

    PlanFeature::factory()->for($plan)->create([
        'feature_key' => 'export_csv',
        'feature_value' => 'true',
        'value_type' => FeatureValueType::Boolean->value,
    ]);
    PlanFeature::factory()->for($plan)->create([
        'feature_key' => 'apps_tracked',
        'feature_value' => '25',
        'value_type' => FeatureValueType::Integer->value,
    ]);
    PlanFeature::factory()->for($plan)->create([
        'feature_key' => 'saved_searches',
        'feature_value' => '',
        'value_type' => FeatureValueType::Unlimited->value,
    ]);

    $plan->load('features');

    expect($plan->features)->toHaveCount(3);
    expect($plan->feature('export_csv')->castedValue())->toBeTrue();
    expect($plan->feature('apps_tracked')->castedValue())->toBe(25);
    expect($plan->feature('saved_searches')->castedValue())->toBeNull();
});

it('filters by public and active scopes', function () {
    Plan::factory()->create(['status' => PlanStatus::Active->value, 'is_public' => true]);
    Plan::factory()->create(['status' => PlanStatus::Active->value, 'is_public' => false]);
    Plan::factory()->create(['status' => PlanStatus::Draft->value, 'is_public' => false]);
    Plan::factory()->create(['status' => PlanStatus::Deprecated->value, 'is_public' => true]);

    expect(Plan::public()->count())->toBe(2);
    expect(Plan::active()->count())->toBe(2);
    expect(Plan::public()->active()->count())->toBe(1);
});
