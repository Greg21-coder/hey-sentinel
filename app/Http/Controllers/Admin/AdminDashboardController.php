<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiBatch;
use App\Models\AiPainPoint;
use App\Models\Account;
use App\Models\ShopifyApp;
use App\Models\StoreReview;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $accountsByStatus = Account::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $aiBatchesByStatus = AiBatch::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $totalReviews = StoreReview::count();
        $processed = StoreReview::where('ai_status', 'processed')->count();
        $pending = StoreReview::where('ai_status', 'pending')->count();
        $batched = StoreReview::where('ai_status', 'batched')->count();

        $sentimentBreakdown = StoreReview::query()
            ->whereNotNull('ai_sentiment')
            ->selectRaw('ai_sentiment, COUNT(*) as count')
            ->groupBy('ai_sentiment')
            ->pluck('count', 'ai_sentiment')
            ->toArray();

        $reviewsPerWeek = $this->buildReviewsPerWeek();

        $painPointCount = DB::table('review_pain_point')->count();
        $uniquePainPoints = AiPainPoint::count();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'accounts'           => $accountsByStatus,
                'total_accounts'     => array_sum($accountsByStatus),
                'total_users'        => User::count(),
                'total_apps'         => ShopifyApp::withUnlisted()->count(),
                'total_reviews'      => $totalReviews,
                'last_scraped_at'    => ShopifyApp::withUnlisted()->max('last_scraped_at'),
            ],
            'pipeline' => [
                'scraped'    => $totalReviews,
                'pending'    => $pending,
                'batched'    => $batched,
                'processed'  => $processed,
                'pain_points' => $painPointCount,
                'unique_pain_points' => $uniquePainPoints,
                'progress_pct' => $totalReviews > 0 ? round(($processed / $totalReviews) * 100, 1) : 0,
            ],
            'ai_batches' => $aiBatchesByStatus,
            'sentimentBreakdown' => $sentimentBreakdown,
            'reviewsPerWeek' => $reviewsPerWeek,
        ]);
    }

    protected function buildReviewsPerWeek(): array
    {
        $labels = [];
        $counts = [];
        for ($i = 11; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = $weekStart->copy()->endOfWeek();
            $labels[] = $weekStart->format('M d');
            $counts[] = StoreReview::whereBetween('created_at', [$weekStart, $weekEnd])->count();
        }
        return ['labels' => $labels, 'counts' => $counts];
    }
}
