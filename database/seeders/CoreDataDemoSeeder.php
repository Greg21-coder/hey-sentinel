<?php

namespace Database\Seeders;

use App\Models\AiPainPoint;
use App\Models\AiTag;
use App\Models\ShopifyAppCategory;
use Illuminate\Database\Seeder;

class CoreDataDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Categories
        collect([
            ['slug' => 'marketing', 'name' => 'Marketing'],
            ['slug' => 'shipping', 'name' => 'Shipping and delivery'],
            ['slug' => 'inventory', 'name' => 'Inventory management'],
            ['slug' => 'reviews', 'name' => 'Reviews and ratings'],
            ['slug' => 'analytics', 'name' => 'Analytics and reports'],
        ])->each(fn ($data) => ShopifyAppCategory::updateOrCreate(['slug' => $data['slug']], $data));

        // Pain point catalog (demo placeholders — AiPainPointVocabularySeeder is authoritative)
        collect([
            ['slug' => 'shipping-delays', 'name' => 'Shipping delays', 'category' => 'shipping'],
            ['slug' => 'ui-confusing', 'name' => 'UI is confusing', 'category' => 'ui_ux'],
            ['slug' => 'billing-surprises', 'name' => 'Unexpected billing', 'category' => 'billing'],
            ['slug' => 'slow-support', 'name' => 'Slow customer support', 'category' => 'support'],
            ['slug' => 'sync-issues', 'name' => 'Data sync issues', 'category' => 'reliability'],
        ])->each(fn ($data) => AiPainPoint::updateOrCreate(['slug' => $data['slug']], $data));

        // Tag catalog
        collect([
            ['slug' => 'performance', 'name' => 'Performance'],
            ['slug' => 'integration', 'name' => 'Integration'],
            ['slug' => 'pricing', 'name' => 'Pricing'],
            ['slug' => 'documentation', 'name' => 'Documentation'],
        ])->each(fn ($data) => AiTag::updateOrCreate(['slug' => $data['slug']], $data));
    }
}
