<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiPainPoint;
use App\Models\ShopifyApp;
use App\Models\StoreReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminPainPointsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $totalReviews   = StoreReview::count();
        $processed      = StoreReview::where('ai_status', 'processed')->count();
        $pending        = StoreReview::where('ai_status', 'pending')->count();
        $painPointLinks = DB::table('review_pain_point')->count();
        $appsAnalyzed   = DB::table('review_pain_point')
            ->join('store_reviews', 'store_reviews.id', '=', 'review_pain_point.review_id')
            ->distinct('store_reviews.shopify_app_id')
            ->count('store_reviews.shopify_app_id');

        $progressPct  = $totalReviews > 0 ? round(($processed / $totalReviews) * 100, 1) : 0;
        $avgPerReview = $processed > 0 ? round($painPointLinks / $processed, 2) : 0;

        $topPainPoints = AiPainPoint::query()
            ->select('ai_pain_points.*')
            ->selectSub(
                DB::table('review_pain_point')
                    ->whereColumn('review_pain_point.pain_point_id', 'ai_pain_points.id')
                    ->selectRaw('COUNT(*)'),
                'occurrences'
            )
            ->selectSub(
                DB::table('review_pain_point')
                    ->join('store_reviews', 'store_reviews.id', '=', 'review_pain_point.review_id')
                    ->whereColumn('review_pain_point.pain_point_id', 'ai_pain_points.id')
                    ->selectRaw('COUNT(DISTINCT store_reviews.shopify_app_id)'),
                'apps_affected'
            )
            ->selectSub(
                DB::table('review_pain_point')
                    ->whereColumn('review_pain_point.pain_point_id', 'ai_pain_points.id')
                    ->selectRaw('AVG(confidence)'),
                'avg_confidence'
            )
            ->selectSub(
                DB::table('review_pain_point')
                    ->whereColumn('review_pain_point.pain_point_id', 'ai_pain_points.id')
                    ->where('severity', 'high')
                    ->selectRaw('COUNT(*)'),
                'high_severity_count'
            )
            ->having('occurrences', '>', 0)
            ->orderByDesc('occurrences')
            ->limit(50)
            ->get();

        $processedSub = DB::table('store_reviews')
            ->whereColumn('store_reviews.shopify_app_id', 'shopify_apps.id')
            ->where('ai_status', 'processed')
            ->selectRaw('COUNT(*)');

        $painCountSub = DB::table('review_pain_point')
            ->join('store_reviews', 'store_reviews.id', '=', 'review_pain_point.review_id')
            ->whereColumn('store_reviews.shopify_app_id', 'shopify_apps.id')
            ->selectRaw('COUNT(*)');

        $topPainSub = DB::table('review_pain_point')
            ->join('store_reviews', 'store_reviews.id', '=', 'review_pain_point.review_id')
            ->join('ai_pain_points', 'ai_pain_points.id', '=', 'review_pain_point.pain_point_id')
            ->whereColumn('store_reviews.shopify_app_id', 'shopify_apps.id')
            ->selectRaw('ai_pain_points.slug')
            ->groupBy('ai_pain_points.slug')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(1);

        $negativePctSub = DB::table('store_reviews')
            ->whereColumn('store_reviews.shopify_app_id', 'shopify_apps.id')
            ->where('ai_status', 'processed')
            ->selectRaw('ROUND(100.0 * SUM(CASE WHEN ai_sentiment = "negative" THEN 1 ELSE 0 END) / NULLIF(COUNT(*),0), 1)');

        $topApps = ShopifyApp::query()
            ->select('shopify_apps.*')
            ->selectSub($processedSub, 'processed_reviews')
            ->selectSub($painCountSub, 'pain_point_count')
            ->selectSub($topPainSub, 'top_pain_point')
            ->selectSub($negativePctSub, 'negative_pct')
            ->having('pain_point_count', '>', 0)
            ->orderByDesc('pain_point_count')
            ->limit(50)
            ->get();

        return Inertia::render('Admin/PainPointsAnalytics', [
            'stats' => [
                'reviews_processed'  => $processed,
                'reviews_pending'    => $pending,
                'pain_point_links'   => $painPointLinks,
                'apps_analyzed'      => $appsAnalyzed,
                'progress_pct'       => $progressPct,
                'avg_per_review'     => $avgPerReview,
            ],
            'topPainPoints' => $topPainPoints,
            'topApps'       => $topApps,
        ]);
    }
}
