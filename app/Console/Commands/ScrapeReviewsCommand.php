<?php

namespace App\Console\Commands;

use App\Jobs\Scraping\ScrapeReviewPageJob;
use App\Models\ShopifyApp;
use Illuminate\Console\Command;

class ScrapeReviewsCommand extends Command
{
    protected $signature = 'app:scrape:reviews {--app= : Specific app ID} {--pages=5 : Pages per app}';

    protected $description = 'Enqueue review-scraping jobs for one or all scraped apps.';

    public function handle(): int
    {
        $appId = $this->option('app');
        $pages = (int) $this->option('pages');

        $query = ShopifyApp::query()->where('scraping_status', 'scraped');
        if ($appId) {
            $query->where('id', $appId);
        }

        $apps = $query->get();
        $count = 0;

        foreach ($apps as $app) {
            for ($p = 1; $p <= $pages; $p++) {
                ScrapeReviewPageJob::dispatch($app->id, $p);
                $count++;
            }
        }

        $this->info("Dispatched {$count} ScrapeReviewPageJob(s) across {$apps->count()} app(s).");

        return self::SUCCESS;
    }
}
