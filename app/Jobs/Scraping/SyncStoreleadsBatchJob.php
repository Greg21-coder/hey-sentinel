<?php

namespace App\Jobs\Scraping;

use App\Models\ShopifyStore;
use App\Models\ShopifyStoreRawPayload;
use App\Services\Scraping\StoreleadsClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncStoreleadsBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @param  list<string>  $storeleadsIds */
    public function __construct(public array $storeleadsIds) {}

    public function handle(StoreleadsClient $client): void
    {
        $records = $client->getStoresByIds($this->storeleadsIds);

        foreach ($records as $record) {
            $domain = $record['domain'] ?? null;
            if ($domain === null) {
                continue;
            }

            $payloadHash = hash('sha256', json_encode($record));

            $store = ShopifyStore::updateOrCreate(
                ['domain' => $domain],
                [
                    'store_name' => $record['store_name'] ?? null,
                    'country_code' => $record['country_code'] ?? null,
                    'language_code' => $record['language_code'] ?? null,
                    'platform_tier' => $record['platform_tier'] ?? null,
                    'estimated_monthly_visits' => $record['estimated_monthly_visits'] ?? null,
                    'estimated_monthly_sales_usd' => $record['estimated_monthly_sales_usd'] ?? null,
                    'employees_estimate' => $record['employees_estimate'] ?? null,
                    'theme_name' => $record['theme_name'] ?? null,
                    'apps_installed_count' => $record['apps_installed_count'] ?? null,
                    'storeleads_id' => $record['id'] ?? null,
                    'storeleads_payload_hash' => $payloadHash,
                    'storeleads_synced_at' => now(),
                    'scraping_status' => 'scraped',
                ]
            );

            ShopifyStoreRawPayload::updateOrCreate(
                ['shopify_store_id' => $store->id],
                ['payload' => $record, 'imported_at' => now()]
            );
        }

        Log::info('SyncStoreleadsBatchJob synced', ['count' => count($records)]);
    }
}
