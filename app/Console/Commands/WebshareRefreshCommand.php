<?php

namespace App\Console\Commands;

use App\Services\Scraping\WebshareProxyManager;
use Illuminate\Console\Command;

class WebshareRefreshCommand extends Command
{
    protected $signature = 'app:webshare:refresh';

    protected $description = 'Refresh the Webshare proxy pool from the API.';

    public function handle(WebshareProxyManager $manager): int
    {
        if (empty(config('scraping.webshare.api_token'))) {
            $this->warn('WEBSHARE_API_TOKEN is not set. Skipping.');

            return self::SUCCESS;
        }

        $count = $manager->refreshPool();
        $this->info("Refreshed pool with {$count} proxies.");

        return self::SUCCESS;
    }
}
