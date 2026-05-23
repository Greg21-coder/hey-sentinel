<?php

namespace Database\Factories;

use App\Enums\FollowedAppKind;
use App\Models\Account;
use App\Models\AccountFollowedApp;
use App\Models\ShopifyApp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountFollowedApp>
 */
class AccountFollowedAppFactory extends Factory
{
    protected $model = AccountFollowedApp::class;

    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'shopify_app_id' => ShopifyApp::factory(),
            'kind' => fake()->randomElement([FollowedAppKind::Mine->value, FollowedAppKind::Competitor->value]),
            'followed_at' => now(),
            'notes' => null,
        ];
    }

    public function mine(): static
    {
        return $this->state(fn () => ['kind' => FollowedAppKind::Mine->value]);
    }

    public function competitor(): static
    {
        return $this->state(fn () => ['kind' => FollowedAppKind::Competitor->value]);
    }
}
