<?php

namespace Database\Factories;

use App\Enums\ScrapingStatus;
use App\Models\ShopifyStore;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopifyStore>
 */
class ShopifyStoreFactory extends Factory
{
    protected $model = ShopifyStore::class;

    public function definition(): array
    {
        return [
            'domain' => fake()->unique()->domainName(),
            'store_name' => fake()->company(),
            'country_code' => strtoupper(fake()->countryCode()),
            'language_code' => 'en',
            'platform_tier' => fake()->randomElement(['basic', 'advanced', 'plus']),
            'estimated_monthly_visits' => fake()->numberBetween(100, 500000),
            'estimated_monthly_sales_usd' => fake()->numberBetween(500, 5000000),
            'employees_estimate' => fake()->numberBetween(1, 200),
            'theme_name' => fake()->randomElement(['Dawn', 'Refresh', 'Sense', 'Studio', 'Crave']),
            'apps_installed_count' => fake()->numberBetween(0, 30),
            'storeleads_id' => fake()->unique()->uuid(),
            'storeleads_payload_hash' => hash('sha256', fake()->text(100)),
            'storeleads_synced_at' => now()->subDays(fake()->numberBetween(0, 30)),
            'scraping_status' => ScrapingStatus::Scraped->value,
        ];
    }
}
