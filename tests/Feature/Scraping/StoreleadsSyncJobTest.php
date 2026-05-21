<?php

use App\Jobs\Scraping\SyncStoreleadsBatchJob;
use App\Models\ShopifyStore;
use App\Services\Scraping\StoreleadsClient;
use Illuminate\Support\Facades\Http;

it('upserts ShopifyStore rows from a Storeleads batch response', function () {
    Http::fake([
        '*/stores/batch' => Http::response([
            'results' => [
                [
                    'id' => 'sl_001',
                    'domain' => 'example.myshopify.com',
                    'store_name' => 'Example Store',
                    'country_code' => 'US',
                    'language_code' => 'en',
                    'platform_tier' => 'plus',
                    'estimated_monthly_visits' => 12000,
                    'estimated_monthly_sales_usd' => 450000,
                    'employees_estimate' => 25,
                    'theme_name' => 'Dawn',
                    'apps_installed_count' => 8,
                ],
            ],
        ], 200),
    ]);

    (new SyncStoreleadsBatchJob(['sl_001']))->handle(app(StoreleadsClient::class));

    $store = ShopifyStore::where('domain', 'example.myshopify.com')->first();
    expect($store)->not->toBeNull();
    expect($store->store_name)->toBe('Example Store');
    expect($store->platform_tier)->toBe('plus');
    expect($store->storeleads_payload_hash)->not->toBeNull();
    expect($store->rawPayload)->not->toBeNull();
});
