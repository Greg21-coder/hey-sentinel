<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function superadmin(): static
    {
        return $this->afterCreating(function (\App\Models\User $user) {
            $internal = \App\Models\Account::firstOrCreate(
                ['slug' => 'heysentinel-internal'],
                [
                    'uuid'          => (string) \Illuminate\Support\Str::uuid(),
                    'name'          => 'HeySentinel Internal',
                    'slug'          => 'heysentinel-internal',
                    'status'        => \App\Enums\AccountStatus::Active->value,
                    'billing_cycle' => \App\Enums\BillingCycle::Monthly->value,
                    'plan_id'       => \App\Models\Plan::first()?->id
                                       ?? \App\Models\Plan::factory()->create()->id,
                ]
            );
            \App\Models\AccountUser::updateOrCreate(
                ['account_id' => $internal->id, 'user_id' => $user->id],
                [
                    'role' => \App\Enums\UserRole::Owner->value,
                    'invitation_accepted_at' => now(),
                ]
            );
        });
    }
}
