<?php

namespace Database\Seeders;

use App\Models\ShopifyApp;
use App\Models\ShopifyAppCategory;
use App\Models\ShopifyStore;
use App\Models\StoreReview;
use Illuminate\Database\Seeder;

class FakeAppCatalogSeeder extends Seeder
{
    /**
     * Generates 20 synthetic apps with 10 reviews each across 40 fake stores.
     *
     * NOT registered in DatabaseSeeder. Run explicitly when fake data is wanted:
     *   php artisan db:seed --class=FakeAppCatalogSeeder
     *
     * Real-data flows use app:seed:handles + app:scrape:* instead.
     */
    public function run(): void
    {
        if (ShopifyApp::count() > 0) {
            $this->command?->warn('FakeAppCatalogSeeder: apps already exist; skipping.');
            return;
        }

        $categories = ShopifyAppCategory::all();
        if ($categories->isEmpty()) {
            $this->command?->error('FakeAppCatalogSeeder: no categories. Run CoreDataDemoSeeder first.');
            return;
        }

        $stores = ShopifyStore::factory()->count(40)->create();

        $apps = ShopifyApp::factory()
            ->count(20)
            ->sequence(fn ($sequence) => ['category_id' => $categories->random()->id])
            ->create();

        $apps->each(function (ShopifyApp $app) use ($stores) {
            StoreReview::factory()
                ->count(10)
                ->sequence(fn ($sequence) => [
                    'shopify_app_id' => $app->id,
                    'shopify_store_id' => $stores->random()->id,
                ])
                ->create();
        });

        $this->command?->info("FakeAppCatalogSeeder: created {$apps->count()} apps with reviews.");
    }
}
