<?php

namespace App\Http\Controllers\Customer;

use App\Enums\FollowedAppKind;
use App\Http\Controllers\Controller;
use App\Models\AccountFollowedApp;
use App\Models\AiPainPoint;
use App\Models\ShopifyApp;
use App\Models\StoreReview;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CustomerDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $accountId = $request->user()->currentAccount?->id ?? 0;

        // Followed app IDs for this account
        $followedIds = AccountFollowedApp::withoutGlobalScope('account')
            ->where('account_id', $accountId)
            ->pluck('shopify_app_id')
            ->toArray();

        // --- Stats ---
        $followedCount = count($followedIds);

        $painPointsCount = 0;
        $negativeCount   = 0;
        $velocityCount   = 0;

        if ($followedIds !== []) {
            $painPointsCount = DB::table('review_pain_point')
                ->join('store_reviews', 'store_reviews.id', '=', 'review_pain_point.review_id')
                ->whereIn('store_reviews.shopify_app_id', $followedIds)
                ->count();

            $negativeCount = StoreReview::whereIn('shopify_app_id', $followedIds)
                ->where('ai_sentiment', 'negative')
                ->count();

            $velocityCount = StoreReview::whereIn('shopify_app_id', $followedIds)
                ->where('published_at', '>=', now()->subWeek())
                ->count();
        }

        $stats = [
            'followed'     => $followedCount,
            'pain_points'  => $painPointsCount,
            'negative'     => $negativeCount,
            'velocity'     => $velocityCount,
        ];

        // --- Sentiment Timeline (12 months) ---
        $sentimentTimeline = $this->buildSentimentTimeline($followedIds);

        // --- Pain Points Radar (top 8) ---
        $painPointsRadar = $this->buildPainPointsRadar($followedIds);

        // --- Review Velocity (12 weeks) ---
        $reviewVelocity = $this->buildReviewVelocity($followedIds);

        // --- My Apps ---
        $kind = $request->input('kind', 'mine');
        $myApps = $this->buildMyApps($accountId, $kind);

        // --- Change Feed ---
        $changeFeed = [];
        if ($followedIds !== []) {
            $changeFeed = \App\Models\AppChange::query()
                ->with('app:id,name')
                ->whereIn('shopify_app_id', $followedIds)
                ->orderByDesc('detected_at')
                ->limit(20)
                ->get()
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'app_name' => $c->app?->name ?? 'Unknown',
                    'app_id' => $c->shopify_app_id,
                    'field' => $c->field,
                    'old_value' => $c->old_value,
                    'new_value' => $c->new_value,
                    'detected_at' => $c->detected_at->toISOString(),
                ])
                ->toArray();
        }

        $mineCount = AccountFollowedApp::withoutGlobalScope('account')
            ->where('account_id', $accountId)
            ->where('kind', FollowedAppKind::Mine->value)
            ->count();
        $needsTour = $mineCount === 0;

        return Inertia::render('Customer/Dashboard', [
            'stats'              => $stats,
            'sentimentTimeline'  => $sentimentTimeline,
            'painPointsRadar'    => $painPointsRadar,
            'reviewVelocity'     => $reviewVelocity,
            'myApps'             => $myApps,
            'changeFeed'         => $changeFeed,
            'onboarding'         => ['needsTour' => $needsTour],
            'kind'               => in_array($kind, ['mine', 'competitor'], true) ? $kind : 'all',
        ]);
    }

    private function buildSentimentTimeline(array $followedIds): array
    {
        // Build 12 empty month buckets
        $months   = [];
        $labels   = [];
        $positive = [];
        $neutral  = [];
        $mixed    = [];
        $negative = [];

        for ($i = 11; $i >= 0; $i--) {
            $key      = now()->subMonths($i)->format('Y-m');
            $months[] = $key;
            $labels[] = now()->subMonths($i)->format('M Y');
            $positive[$key] = 0;
            $neutral[$key]  = 0;
            $mixed[$key]    = 0;
            $negative[$key] = 0;
        }

        if ($followedIds !== []) {
            $rows = StoreReview::selectRaw("DATE_FORMAT(published_at, '%Y-%m') as month, ai_sentiment, COUNT(*) as cnt")
                ->whereIn('shopify_app_id', $followedIds)
                ->where('published_at', '>=', now()->subMonths(12)->startOfMonth())
                ->whereNotNull('ai_sentiment')
                ->groupByRaw("DATE_FORMAT(published_at, '%Y-%m'), ai_sentiment")
                ->get();

            foreach ($rows as $row) {
                $month = $row->month;
                if (! isset($positive[$month])) {
                    continue;
                }
                match ($row->ai_sentiment) {
                    'positive' => $positive[$month] += $row->cnt,
                    'neutral'  => $neutral[$month]  += $row->cnt,
                    'mixed'    => $mixed[$month]    += $row->cnt,
                    'negative' => $negative[$month] += $row->cnt,
                    default    => null,
                };
            }
        }

        return [
            'labels'   => $labels,
            'positive' => array_values($positive),
            'neutral'  => array_values($neutral),
            'mixed'    => array_values($mixed),
            'negative' => array_values($negative),
        ];
    }

    private function buildPainPointsRadar(array $followedIds): array
    {
        if ($followedIds === []) {
            return ['labels' => [], 'counts' => []];
        }

        $rows = DB::table('review_pain_point')
            ->join('store_reviews', 'store_reviews.id', '=', 'review_pain_point.review_id')
            ->join('ai_pain_points', 'ai_pain_points.id', '=', 'review_pain_point.pain_point_id')
            ->whereIn('store_reviews.shopify_app_id', $followedIds)
            ->selectRaw('ai_pain_points.name, COUNT(*) as cnt')
            ->groupBy('ai_pain_points.name')
            ->orderByDesc('cnt')
            ->limit(8)
            ->get();

        return [
            'labels' => $rows->pluck('name')->toArray(),
            'counts' => $rows->pluck('cnt')->map(fn ($v) => (int) $v)->toArray(),
        ];
    }

    private function buildReviewVelocity(array $followedIds): array
    {
        $labels = [];
        $counts = [];

        for ($i = 11; $i >= 0; $i--) {
            $start  = now()->subWeeks($i)->startOfWeek();
            $end    = now()->subWeeks($i)->endOfWeek();
            $labels[] = $start->format('M d');

            $cnt = 0;
            if ($followedIds !== []) {
                $cnt = StoreReview::whereIn('shopify_app_id', $followedIds)
                    ->whereBetween('published_at', [$start, $end])
                    ->count();
            }
            $counts[] = $cnt;
        }

        return [
            'labels' => $labels,
            'counts' => $counts,
        ];
    }

    private function buildMyApps(int $accountId, string $kind = 'mine'): array
    {
        return DB::table('account_followed_apps')
            ->join('shopify_apps', 'shopify_apps.id', '=', 'account_followed_apps.shopify_app_id')
            ->where('account_followed_apps.account_id', $accountId)
            ->when(in_array($kind, ['mine', 'competitor'], true), fn ($q) => $q->where('account_followed_apps.kind', $kind))
            ->whereNull('shopify_apps.unlisted_at')
            ->select([
                'shopify_apps.id',
                'shopify_apps.name',
                'shopify_apps.average_rating as rating',
                'shopify_apps.total_reviews as reviews',
                'shopify_apps.ai_summary',
                'account_followed_apps.kind as pivot_kind',
                'account_followed_apps.followed_at as pivot_followed_at',
            ])
            ->orderBy('account_followed_apps.followed_at', 'desc')
            ->get()
            ->toArray();
    }
}
