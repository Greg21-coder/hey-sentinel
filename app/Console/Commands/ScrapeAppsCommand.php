<?php

namespace App\Console\Commands;

use App\Enums\ScrapingStatus;
use App\Jobs\Scraping\ScrapeAppPageJob;
use App\Models\ShopifyApp;
use Illuminate\Console\Command;

class ScrapeAppsCommand extends Command
{
    protected $signature = 'app:scrape:apps {--limit=300 : Maximum apps to enqueue}';

    protected $description = 'Enqueue scraping jobs for pending Shopify apps (priority by last_scraped_at ASC).';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $apps = ShopifyApp::query()
            ->whereIn('scraping_status', [ScrapingStatus::Pending->value, ScrapingStatus::Error->value])
            ->orderBy('last_scraped_at')
            ->limit($limit)
            ->get();

        foreach ($apps as $app) {
            ScrapeAppPageJob::dispatch($app->shopify_app_handle);
        }

        $this->info("Dispatched {$apps->count()} ScrapeAppPageJob(s).");

        return self::SUCCESS;
    }
}
