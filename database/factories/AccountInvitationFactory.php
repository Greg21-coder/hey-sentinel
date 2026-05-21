<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Account;
use App\Models\AccountInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountInvitation>
 */
class AccountInvitationFactory extends Factory
{
    protected $model = AccountInvitation::class;

    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'email' => fake()->unique()->safeEmail(),
            'role' => UserRole::Member->value,
            'invited_by' => User::factory(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'accepted_at' => null,
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'accepted_at' => null,
            'expires_at' => now()->subDay(),
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn () => [
            'accepted_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);
    }
}
