<?php

namespace App\Console\Commands;

use App\Jobs\Scraping\SyncStoreleadsBatchJob;
use App\Models\ShopifyStore;
use Illuminate\Console\Command;

class StoreleadsSyncCommand extends Command
{
    protected $signature = 'app:storeleads:sync {--scope=reviewers : reviewers|all}';

    protected $description = 'Enqueue Storeleads sync batches for stores that need refresh.';

    public function handle(): int
    {
        $chunkSize = (int) config('scraping.rate_limits.storeleads_chunk_size');

        $query = ShopifyStore::query()->whereNotNull('storeleads_id');

        if ($this->option('scope') === 'reviewers') {
            // Tier 0 scope: only stores that have ever posted a review.
            $query->whereHas('reviews');
        }

        $batches = 0;
        $query->chunkById($chunkSize, function ($stores) use (&$batches) {
            SyncStoreleadsBatchJob::dispatch($stores->pluck('storeleads_id')->all());
            $batches++;
        });

        $this->info("Dispatched {$batches} SyncStoreleadsBatchJob(s).");

        return self::SUCCESS;
    }
}
