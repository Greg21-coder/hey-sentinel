<?php

namespace App\Jobs\Scraping;

use App\Enums\ScrapingStatus;
use App\Jobs\Scraping\ScrapeAppPageJob;
use App\Models\DiscoveryRun;
use App\Models\ShopifyApp;
use App\Services\Scraping\ShopifySitemapParser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DiscoverAppsFromSitemapJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public DiscoveryRun $run) {}

    public function handle(ShopifySitemapParser $parser): void
    {
        $this->run->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $xml = $parser->fetch();
            $handles = $parser->extractHandles($xml);

            $this->run->update(['apps_found' => $handles->count()]);

            $new = 0;
            $existing = 0;
            $limit = $this->run->apps_limit;
            $newHandles = [];

            foreach ($handles as $handle) {
                if ($limit !== null && $new >= $limit) {
                    break;
                }

                $app = ShopifyApp::firstOrCreate(
                    ['shopify_app_handle' => $handle],
                    [
                        'name' => $handle,
                        'developer_name' => 'pending-discovery',
                        'scraping_status' => ScrapingStatus::Pending->value,
                    ]
                );

                if ($app->wasRecentlyCreated) {
                    $new++;
                    $newHandles[] = $handle;
                } else {
                    $existing++;
                }
            }

            $this->run->update([
                'status' => 'completed',
                'completed_at' => now(),
                'apps_new' => $new,
                'apps_existing' => $existing,
            ]);

            $delaySeconds = 0;
            collect($newHandles)->chunk(200)->each(function ($chunk) use (&$delaySeconds) {
                foreach ($chunk as $handle) {
                    ScrapeAppPageJob::dispatch($handle)->delay(now()->addSeconds($delaySeconds));
                }
                $delaySeconds += 90;
            });
        } catch (\Throwable $e) {
            $this->run->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
