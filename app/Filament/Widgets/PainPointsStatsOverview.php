<?php

namespace App\Filament\Widgets;

use App\Enums\AiStatus;
use App\Models\ShopifyApp;
use App\Models\StoreReview;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class PainPointsStatsOverview extends BaseWidget
{
    protected ?string $heading = 'Pipeline Overview';

    protected function getStats(): array
    {
        $totalReviews = StoreReview::count();
        $processed = StoreReview::where('ai_status', AiStatus::Processed->value)->count();
        $pending = StoreReview::where('ai_status', AiStatus::Pending->value)->count();
        $painPointLinks = DB::table('review_pain_point')->count();
        $appsAnalyzed = DB::table('review_pain_point')
            ->join('store_reviews', 'store_reviews.id', '=', 'review_pain_point.review_id')
            ->distinct('store_reviews.shopify_app_id')
            ->count('store_reviews.shopify_app_id');

        $progressPct = $totalReviews > 0 ? round(($processed / $totalReviews) * 100, 1) : 0;
        $avgPerReview = $processed > 0 ? round($painPointLinks / $processed, 2) : 0;

        return [
            Stat::make('Reviews Processed', number_format($processed))
                ->description("{$progressPct}% of {$totalReviews} total")
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('success'),

            Stat::make('Pending in Queue', number_format($pending))
                ->description('Awaiting AI extraction')
                ->descriptionIcon('heroicon-m-queue-list')
                ->color($pending > 0 ? 'warning' : 'gray'),

            Stat::make('Pain Points Extracted', number_format($painPointLinks))
                ->description("Avg {$avgPerReview} per review")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('info'),

            Stat::make('Apps with AI Signals', number_format($appsAnalyzed).' / '.ShopifyApp::count())
                ->description('Coverage across catalog')
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('primary'),
        ];
    }
}
