<?php

namespace App\Jobs\Scraping;

use App\Jobs\Ai\CompileBatchJob;
use App\Models\ShopifyApp;
use App\Services\Scraping\ParsedReview;
use App\Services\Scraping\ShopifyReviewPageParser;
use App\Services\Scraping\ShopifyReviewScraper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScrapeReviewPageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $appId, public int $page = 1) {}

    public function middleware(): array
    {
        return [(new RateLimited('shopify-scrape'))];
    }

    public function handle(ShopifyReviewScraper $scraper, ShopifyReviewPageParser $parser): void
    {
        $app = ShopifyApp::findOrFail($this->appId);

        $response = $scraper->fetch($app->scrapingHandle(), $this->page);
        if ($response === null) {
            $this->release(60);

            return;
        }

        if ($response->failed()) {
            Log::warning('ScrapeReviewPageJob failed', [
                'app_id' => $app->id,
                'handle' => $app->shopify_app_handle,
                'page' => $this->page,
                'status' => $response->status(),
            ]);

            return;
        }

        $parsedPage = $parser->parse($response->body());

        $rows = array_map(
            fn (ParsedReview $r) => $this->toRow($app->id, $r),
            $parsedPage->reviews
        );

        $inserted = empty($rows) ? 0 : DB::table('store_reviews')->insertOrIgnore($rows);

        if ($parsedPage->hasNextPage) {
            self::dispatch($app->id, $this->page + 1)->onQueue('scrape-reviews');
        }

        if ($inserted > 0) {
            CompileBatchJob::dispatch()->delay(now()->addSeconds(30));
        }

        Log::info('ScrapeReviewPageJob success', [
            'app_id' => $app->id,
            'page' => $this->page,
            'parsed' => count($rows),
            'inserted' => $inserted,
            'has_next' => $parsedPage->hasNextPage,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function toRow(int $appId, ParsedReview $review): array
    {
        $publishedAt = $review->publishedAt->format('Y-m-d H:i:s');

        return [
            'shopify_app_id' => $appId,
            'shopify_store_id' => null,
            'reviewer_name' => mb_substr($review->reviewerName, 0, 255),
            'rating' => $review->rating,
            'review_text' => $review->reviewText,
            'review_text_hash' => hash('sha256', $review->reviewText),
            'language_code' => null,
            'ai_status' => 'pending',
            'ai_sentiment' => null,
            'ai_processed_at' => null,
            'published_at' => $publishedAt,
            'created_at' => now()->format('Y-m-d H:i:s'),
        ];
    }
}
