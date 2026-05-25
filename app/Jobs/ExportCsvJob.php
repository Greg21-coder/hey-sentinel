<?php

namespace App\Jobs;

use App\Models\AccountFollowedApp;
use App\Models\ShopifyApp;
use App\Models\StoreReview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ExportCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public readonly int    $accountId,
        public readonly string $type,    // 'apps' | 'reviews'
        public readonly array  $filters = [],
    ) {}

    public function handle(): void
    {
        $path = "exports/{$this->accountId}/";

        match ($this->type) {
            'apps'    => $this->exportApps($path),
            'reviews' => $this->exportReviews($path),
            default   => null,
        };
    }

    private function exportApps(string $dir): void
    {
        $filename = $dir.'apps_'.now()->format('Ymd_His').'.csv';

        $headers = ['ID', 'Name', 'Developer', 'Category', 'Rating', 'Reviews', 'Pricing Has Free', 'Pricing Min USD', 'Listed At'];

        $query = ShopifyApp::with('category')
            ->when($this->filters['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->when($this->filters['rating_min'] ?? null, fn ($q, $v) => $q->where('average_rating', '>=', $v))
            ->when(($this->filters['pricing'] ?? 'all') === 'free', fn ($q) => $q->where('pricing_has_free', true))
            ->when(($this->filters['pricing'] ?? 'all') === 'paid', fn ($q) => $q->where('pricing_has_free', false))
            ->when($this->filters['keyword'] ?? null, fn ($q, $v) => $q->where(function ($inner) use ($v) {
                $inner->where('name', 'LIKE', "%{$v}%")->orWhere('description', 'LIKE', "%{$v}%");
            }));

        $this->writeCsv($filename, $headers, function () use ($query) {
            foreach ($query->cursor() as $app) {
                yield [
                    $app->id,
                    $app->name,
                    $app->developer_name,
                    $app->category?->name,
                    $app->average_rating,
                    $app->total_reviews,
                    $app->pricing_has_free ? 'yes' : 'no',
                    $app->pricing_min_usd,
                    $app->created_at?->toDateString(),
                ];
            }
        });
    }

    private function exportReviews(string $dir): void
    {
        $appId    = $this->filters['shopify_app_id'] ?? null;
        $filename = $dir.'reviews_app'.$appId.'_'.now()->format('Ymd_His').'.csv';

        $headers = ['ID', 'App ID', 'Reviewer', 'Rating', 'Sentiment', 'Published At', 'Review Text'];

        $query = StoreReview::when($appId, fn ($q) => $q->where('shopify_app_id', $appId));

        $this->writeCsv($filename, $headers, function () use ($query) {
            foreach ($query->cursor() as $review) {
                yield [
                    $review->id,
                    $review->shopify_app_id,
                    $review->reviewer_name,
                    $review->rating,
                    $review->ai_sentiment,
                    $review->published_at?->toDateTimeString(),
                    $review->review_text,
                ];
            }
        });
    }

    private function writeCsv(string $path, array $headers, callable $rowsCallback): void
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, $headers);

        foreach ($rowsCallback() as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        Storage::put($path, $content);
    }
}
