<?php

namespace Database\Seeders;

use App\Models\AiPainPoint;
use App\Models\AiTag;
use App\Models\ShopifyApp;
use App\Models\ShopifyAppCategory;
use App\Models\ShopifyStore;
use App\Models\StoreReview;
use Illuminate\Database\Seeder;

class CoreDataDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Categories
        $categories = collect([
            ['slug' => 'marketing', 'name' => 'Marketing'],
            ['slug' => 'shipping', 'name' => 'Shipping and delivery'],
            ['slug' => 'inventory', 'name' => 'Inventory management'],
            ['slug' => 'reviews', 'name' => 'Reviews and ratings'],
            ['slug' => 'analytics', 'name' => 'Analytics and reports'],
        ])->map(fn ($data) => ShopifyAppCategory::updateOrCreate(['slug' => $data['slug']], $data));

        // Pain point catalog
        $painPoints = collect([
            ['slug' => 'shipping-delays', 'name' => 'Shipping delays', 'category' => 'shipping'],
            ['slug' => 'ui-confusing', 'name' => 'UI is confusing', 'category' => 'ui_ux'],
            ['slug' => 'billing-surprises', 'name' => 'Unexpected billing', 'category' => 'billing'],
            ['slug' => 'slow-support', 'name' => 'Slow customer support', 'category' => 'support'],
            ['slug' => 'sync-issues', 'name' => 'Data sync issues', 'category' => 'reliability'],
        ])->map(fn ($data) => AiPainPoint::updateOrCreate(['slug' => $data['slug']], $data));

        // Tag catalog
        collect([
            ['slug' => 'performance', 'name' => 'Performance'],
            ['slug' => 'integration', 'name' => 'Integration'],
            ['slug' => 'pricing', 'name' => 'Pricing'],
            ['slug' => 'documentation', 'name' => 'Documentation'],
        ])->each(fn ($data) => AiTag::updateOrCreate(['slug' => $data['slug']], $data));

        // Idempotency: only seed if no apps exist yet
        if (ShopifyApp::count() > 0) {
            return;
        }

        $stores = ShopifyStore::factory()->count(40)->create();

        $apps = ShopifyApp::factory()
            ->count(20)
            ->sequence(fn ($sequence) => ['category_id' => $categories->random()->id])
            ->create();

        // Generate ~10 reviews per app across recent published_at dates
        $apps->each(function (ShopifyApp $app) use ($stores) {
            StoreReview::factory()
                ->count(10)
                ->sequence(fn ($sequence) => [
                    'shopify_app_id' => $app->id,
                    'shopify_store_id' => $stores->random()->id,
                ])
                ->create();
        });
    }
}
