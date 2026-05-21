<?php

namespace Database\Factories;

use App\Enums\FeatureValueType;
use App\Models\Plan;
use App\Models\PlanFeature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanFeature>
 */
class PlanFeatureFactory extends Factory
{
    protected $model = PlanFeature::class;

    public function definition(): array
    {
        return [
            'plan_id' => Plan::factory(),
            'feature_key' => fake()->randomElement(config('features')),
            'feature_value' => 'true',
            'value_type' => FeatureValueType::Boolean->value,
        ];
    }
}
