<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            DemoAccountSeeder::class,
            CoreDataDemoSeeder::class,
            AiPainPointVocabularySeeder::class,
            // Generates 20 synthetic apps + 200 reviews when ShopifyApp is
            // empty. Self-skips when real-data scrape has populated the table
            // (its own ShopifyApp::count() > 0 guard), so this is safe to run
            // by default and idempotent in both demo and real-data flows.
            FakeAppCatalogSeeder::class,
            CustomerDemoSeeder::class,
        ]);
    }
}
