<?php

namespace Database\Factories;

use App\Enums\ScrapingStatus;
use App\Models\ShopifyApp;
use App\Models\ShopifyAppCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ShopifyApp>
 */
class ShopifyAppFactory extends Factory
{
    protected $model = ShopifyApp::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();
        $hasFree = fake()->boolean(30);

        return [
            'shopify_app_handle' => Str::slug($name).'-'.fake()->unique()->randomNumber(5),
            'name' => $name,
            'developer_name' => fake()->company(),
            'developer_url' => fake()->url(),
            'category_id' => ShopifyAppCategory::factory(),
            'description' => fake()->paragraph(3),
            'pricing_raw' => fake()->sentence(),
            'pricing_structured' => ['plans' => [['name' => 'Basic', 'price' => fake()->randomFloat(2, 0, 99)]]],
            'pricing_min_usd' => $hasFree ? 0 : fake()->randomFloat(2, 5, 199),
            'pricing_has_free' => $hasFree,
            'avatar_url' => fake()->imageUrl(80, 80),
            'average_rating' => fake()->randomFloat(2, 3.0, 5.0),
            'total_reviews' => fake()->numberBetween(0, 2000),
            'total_installs_estimate' => fake()->numberBetween(50, 50000),
            'scraping_status' => ScrapingStatus::Scraped->value,
            'last_scraped_at' => now()->subHours(fake()->numberBetween(1, 168)),
        ];
    }
}
