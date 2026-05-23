<?php

namespace Database\Seeders;

use App\Enums\FollowedAppKind;
use App\Models\Account;
use App\Models\AccountFollowedApp;
use App\Models\ShopifyApp;
use Illuminate\Database\Seeder;

class CustomerDemoSeeder extends Seeder
{
    public function run(): void
    {
        $account = Account::where('slug', 'acme-corp')->first();
        if ($account === null) {
            $this->command?->warn('CustomerDemoSeeder: acme-corp account not found; skipping.');
            return;
        }

        $apps = ShopifyApp::query()
            ->withCount(['reviews as processed_count' => function ($q) {
                $q->where('ai_status', 'processed');
            }])
            ->orderByDesc('processed_count')
            ->limit(5)
            ->get();

        if ($apps->count() < 5) {
            $apps = ShopifyApp::query()
                ->orderByDesc('total_reviews')
                ->limit(5)
                ->get();
        }

        AccountFollowedApp::disable();

        foreach ($apps as $index => $app) {
            AccountFollowedApp::updateOrCreate(
                ['account_id' => $account->id, 'shopify_app_id' => $app->id],
                [
                    'kind' => $index === 0
                        ? FollowedAppKind::Mine->value
                        : FollowedAppKind::Competitor->value,
                    'followed_at' => now()->subDays($index + 1),
                    'notes' => $index === 0 ? 'My flagship app — Acme Inventory' : null,
                ]
            );
        }

        AccountFollowedApp::enable();

        $this->command?->info("CustomerDemoSeeder: attached {$apps->count()} apps to acme-corp.");
    }
}
