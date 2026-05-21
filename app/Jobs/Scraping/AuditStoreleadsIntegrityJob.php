<?php

namespace App\Jobs\Scraping;

use App\Models\AuditLog;
use App\Models\ShopifyStore;
use App\Services\Scraping\StoreleadsClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AuditStoreleadsIntegrityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $chunkSize = 1000) {}

    public function handle(StoreleadsClient $client): void
    {
        $drifted = 0;

        ShopifyStore::query()
            ->whereNotNull('storeleads_id')
            ->orderBy('id')
            ->chunkById($this->chunkSize, function ($stores) use ($client, &$drifted) {
                $ids = $stores->pluck('storeleads_id')->all();
                $records = $client->getStoresByIds($ids);
                $byId = collect($records)->keyBy('id');

                foreach ($stores as $store) {
                    $record = $byId->get($store->storeleads_id);
                    if ($record === null) {
                        continue;
                    }
                    $hash = hash('sha256', json_encode($record));
                    if ($hash !== $store->storeleads_payload_hash) {
                        $drifted++;
                        SyncStoreleadsBatchJob::dispatch([$store->storeleads_id]);
                    }
                }
            });

        AuditLog::create([
            'action' => 'storeleads.integrity_audit',
            'metadata' => ['drifted_count' => $drifted],
            'occurred_at' => now(),
        ]);
    }
}
