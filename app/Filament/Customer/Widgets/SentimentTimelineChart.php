<?php

namespace App\Filament\Customer\Widgets;

use App\Models\AccountFollowedApp;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SentimentTimelineChart extends ChartWidget
{
    protected ?string $heading = 'Sentiment over time';

    protected ?string $description = 'Monthly review sentiment across your followed apps';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '320px';

    public ?string $filter = '12m';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            '6m' => 'Last 6 months',
            '12m' => 'Last 12 months',
            '24m' => 'Last 24 months',
        ];
    }

    protected function getData(): array
    {
        $months = match ($this->filter) {
            '6m' => 6,
            '24m' => 24,
            default => 12,
        };

        $appIds = AccountFollowedApp::query()->pluck('shopify_app_id');

        $start = now()->subMonthsNoOverflow($months - 1)->startOfMonth();

        // Pre-build the chronological bucket list so months with no reviews
        // still appear on the X axis instead of collapsing the timeline.
        $buckets = [];
        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonthsNoOverflow($i);
            $key = $month->format('Y-m');
            $buckets[$key] = [
                'label' => $month->format('M Y'),
                'positive' => 0,
                'neutral' => 0,
                'mixed' => 0,
                'negative' => 0,
            ];
        }

        if ($appIds->isNotEmpty()) {
            $rows = DB::table('store_reviews')
                ->whereIn('shopify_app_id', $appIds)
                ->whereNotNull('ai_sentiment')
                ->where('published_at', '>=', $start)
                ->selectRaw("DATE_FORMAT(published_at, '%Y-%m') as ym, ai_sentiment, COUNT(*) as cnt")
                ->groupBy('ym', 'ai_sentiment')
                ->get();

            foreach ($rows as $row) {
                if (! isset($buckets[$row->ym])) {
                    continue;
                }
                if (! array_key_exists($row->ai_sentiment, $buckets[$row->ym])) {
                    continue;
                }
                $buckets[$row->ym][$row->ai_sentiment] = (int) $row->cnt;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Positive',
                    'data' => array_column($buckets, 'positive'),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.20)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Neutral',
                    'data' => array_column($buckets, 'neutral'),
                    'backgroundColor' => 'rgba(148, 163, 184, 0.20)',
                    'borderColor' => 'rgb(148, 163, 184)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Mixed',
                    'data' => array_column($buckets, 'mixed'),
                    'backgroundColor' => 'rgba(234, 179, 8, 0.20)',
                    'borderColor' => 'rgb(234, 179, 8)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Negative',
                    'data' => array_column($buckets, 'negative'),
                    'backgroundColor' => 'rgba(239, 68, 68, 0.20)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => array_column($buckets, 'label'),
        ];
    }

    protected function getOptions(): ?array
    {
        return [
            'scales' => [
                'y' => [
                    'stacked' => true,
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                ],
                'x' => [
                    'stacked' => true,
                ],
            ],
            'plugins' => [
                'legend' => ['position' => 'bottom'],
            ],
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
        ];
    }
}
