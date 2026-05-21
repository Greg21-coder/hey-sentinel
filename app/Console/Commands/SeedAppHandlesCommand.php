<?php

namespace App\Console\Commands;

use App\Enums\ScrapingStatus;
use App\Models\ShopifyApp;
use Illuminate\Console\Command;

class SeedAppHandlesCommand extends Command
{
    protected $signature = 'app:seed:handles {--file=database/data/shopify_app_handles.csv : CSV path (header line "handle")}';

    protected $description = 'Seed shopify_apps with handles from a CSV so the scraper has a working set.';

    public function handle(): int
    {
        $file = base_path($this->option('file'));

        if (! is_file($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        $handles = collect(file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES))
            ->map(fn ($l) => trim($l))
            ->reject(fn ($l) => $l === '' || strcasecmp($l, 'handle') === 0)
            ->unique()
            ->values();

        $created = 0;
        $existed = 0;

        foreach ($handles as $handle) {
            $row = ShopifyApp::firstOrCreate(
                ['shopify_app_handle' => $handle],
                [
                    'name' => $handle,
                    'developer_name' => 'pending-discovery',
                    'scraping_status' => ScrapingStatus::Pending->value,
                ]
            );

            $row->wasRecentlyCreated ? $created++ : $existed++;
        }

        $this->info("Seeded {$handles->count()} handles. Created: {$created}, existed: {$existed}.");

        return self::SUCCESS;
    }
}
