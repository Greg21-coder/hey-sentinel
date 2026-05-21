<?php

namespace Database\Factories;

use App\Enums\PlanStatus;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(4),
            'name' => $name,
            'description' => fake()->sentence(),
            'monthly_price' => fake()->randomFloat(2, 0, 500),
            'yearly_price' => fake()->randomFloat(2, 0, 5000),
            'sort_order' => fake()->numberBetween(0, 100),
            'status' => PlanStatus::Active->value,
            'is_public' => true,
        ];
    }

    public function free(): static
    {
        return $this->state(fn () => [
            'slug' => 'free',
            'name' => 'Free',
            'monthly_price' => 0,
            'yearly_price' => 0,
            'sort_order' => 10,
        ]);
    }

    public function premium(): static
    {
        return $this->state(fn () => [
            'slug' => 'premium',
            'name' => 'Premium',
            'monthly_price' => 49.00,
            'yearly_price' => 490.00,
            'sort_order' => 20,
        ]);
    }

    public function agency(): static
    {
        return $this->state(fn () => [
            'slug' => 'agency',
            'name' => 'Agency',
            'monthly_price' => 199.00,
            'yearly_price' => 1990.00,
            'sort_order' => 30,
        ]);
    }

    public function deprecated(): static
    {
        return $this->state(fn () => ['status' => PlanStatus::Deprecated->value]);
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => PlanStatus::Draft->value,
            'is_public' => false,
        ]);
    }
}
