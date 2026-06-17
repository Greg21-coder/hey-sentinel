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

    public function mine(Account $account): static
    {
        return $this->state(fn () => [
            'account_id' => $account->id,
            'kind' => FollowedAppKind::Mine->value,
            'followed_at' => now(),
        ]);
    }

    public function competitor(Account $account): static
    {
        return $this->state(fn () => [
            'account_id' => $account->id,
            'kind' => FollowedAppKind::Competitor->value,
            'followed_at' => now(),
        ]);
    }
}
