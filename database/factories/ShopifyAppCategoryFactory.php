<?php

namespace Database\Factories;

use App\Models\ShopifyAppCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ShopifyAppCategory>
 */
class ShopifyAppCategoryFactory extends Factory
{
    protected $model = ShopifyAppCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(4),
            'name' => ucfirst($name),
            'parent_id' => null,
        ];
    }
}
