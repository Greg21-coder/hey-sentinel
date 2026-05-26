<?php

namespace App\Console\Commands;

use App\Jobs\Scraping\DiscoverAppsFromSitemapJob;
use App\Models\DiscoveryRun;
use Illuminate\Console\Command;

class DiscoverAppsCommand extends Command
{
    protected $signature = 'app:discover:apps {--limit= : Max new apps to insert (default from config)}';

    protected $description = 'Discover new Shopify apps from the App Store sitemap.';

    public function handle(): int
    {
        $limit = (int) ($this->option('limit') ?: config('scraping.discovery.default_limit'));

        $run = DiscoveryRun::create([
            'source' => 'sitemap',
            'status' => 'pending',
            'triggered_by' => 'cron',
            'apps_limit' => $limit,
        ]);

        DiscoverAppsFromSitemapJob::dispatch($run);

        $this->info("Discovery run #{$run->id} enqueued (limit: {$limit}).");

        return self::SUCCESS;
    }
}
