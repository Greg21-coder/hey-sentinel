<?php

namespace App\Console\Commands;

use App\Enums\ScrapingStatus;
use App\Jobs\Scraping\ScrapeAppPageJob;
use App\Models\ShopifyApp;
use Illuminate\Console\Command;

class ScrapeAppsCommand extends Command
{
    protected $signature = 'app:scrape:apps {--limit=300 : Maximum apps to enqueue} {--rescrape : Re-scrape already-scraped apps (stalest first)}';

    protected $description = 'Enqueue scraping jobs for pending or stale Shopify apps.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        if ($this->option('rescrape')) {
            $apps = ShopifyApp::query()
                ->where('scraping_status', ScrapingStatus::Scraped->value)
                ->orderBy('last_scraped_at')
                ->limit($limit)
                ->get();
        } else {
            $apps = ShopifyApp::query()
                ->whereIn('scraping_status', [ScrapingStatus::Pending->value, ScrapingStatus::Error->value])
                ->orderBy('last_scraped_at')
                ->limit($limit)
                ->get();
        }

        $delaySeconds = 0;
        $apps->chunk(200)->each(function ($chunk) use (&$delaySeconds) {
            foreach ($chunk as $app) {
                ScrapeAppPageJob::dispatch($app->shopify_app_handle)->delay(now()->addSeconds($delaySeconds));
            }
            $delaySeconds += 90;
        });

        $this->info("Dispatched {$apps->count()} ScrapeAppPageJob(s).");

        return self::SUCCESS;
    }
}
