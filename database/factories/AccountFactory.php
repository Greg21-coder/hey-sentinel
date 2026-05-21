<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Enums\BillingCycle;
use App\Models\Account;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'uuid' => (string) Str::uuid(),
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(4),
            'name' => $name,
            'status' => AccountStatus::Active->value,
            'plan_id' => Plan::factory(),
            'billing_cycle' => BillingCycle::Monthly->value,
        ];
    }

    public function onTrial(): static
    {
        return $this->state(fn () => [
            'status' => AccountStatus::Trial->value,
            'billing_cycle' => BillingCycle::None->value,
            'trial_started_at' => now(),
            'free_trial_ends_at' => now()->addDays(14),
        ]);
    }

    public function trialExpired(): static
    {
        return $this->state(fn () => [
            'status' => AccountStatus::Trial->value,
            'billing_cycle' => BillingCycle::None->value,
            'trial_started_at' => now()->subDays(15),
            'free_trial_ends_at' => now()->subDay(),
        ]);
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => AccountStatus::Active->value,
            'billing_cycle' => BillingCycle::Monthly->value,
            'current_period_starts_at' => now()->subDays(15),
            'current_period_ends_at' => now()->addDays(15),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => AccountStatus::Cancelled->value,
            'billing_cycle' => BillingCycle::None->value,
        ]);
    }
}
