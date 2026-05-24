<?php

namespace App\Filament\Customer\Widgets;

use App\Models\AccountFollowedApp;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MyAppsStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Explicit account filter — do not rely on the BelongsToAccount global
        // scope alone. The scope silently skips when currentAccount is null
        // (e.g., user with no account_user pivot), which would leak data across
        // tenants. ?? 0 forces an empty result in that edge case.
        $accountId = Auth::user()?->currentAccount?->id ?? 0;
        $appIds = AccountFollowedApp::query()
            ->where('account_id', $accountId)
            ->pluck('shopify_app_id');

        if ($appIds->isEmpty()) {
            return [
                Stat::make('Apps followed', '0')->description('Add apps from Browse'),
                Stat::make('Pain points detected', '0'),
                Stat::make('Negative reviews', '0'),
            ];
        }

        $painPoints = DB::table('review_pain_point')
            ->join('store_reviews', 'store_reviews.id', '=', 'review_pain_point.review_id')
            ->whereIn('store_reviews.shopify_app_id', $appIds)
            ->count();

        $negative = DB::table('store_reviews')
            ->whereIn('shopify_app_id', $appIds)
            ->where('ai_sentiment', 'negative')
            ->count();

        return [
            Stat::make('Apps followed', $appIds->count())
                ->description('Across My apps + Competitors')
                ->color('primary'),
            Stat::make('Pain points detected', $painPoints)
                ->description('In followed apps')
                ->color('warning'),
            Stat::make('Negative reviews', $negative)
                ->description('In followed apps')
                ->color('danger'),
        ];
    }
}
