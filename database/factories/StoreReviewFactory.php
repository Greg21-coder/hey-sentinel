<?php

namespace Database\Factories;

use App\Enums\AiStatus;
use App\Models\ShopifyApp;
use App\Models\ShopifyStore;
use App\Models\StoreReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StoreReview>
 */
class StoreReviewFactory extends Factory
{
    protected $model = StoreReview::class;

    public function definition(): array
    {
        $text = fake()->paragraph(2);

        return [
            'shopify_app_id' => ShopifyApp::factory(),
            'shopify_store_id' => ShopifyStore::factory(),
            'reviewer_name' => fake()->name(),
            'rating' => fake()->numberBetween(1, 5),
            'review_text' => $text,
            'review_text_hash' => hash('sha256', $text),
            'language_code' => 'en',
            'ai_status' => AiStatus::Pending->value,
            'published_at' => fake()->dateTimeBetween('-2 years', 'now'),
        ];
    }
}
