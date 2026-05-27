<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ScrapingStatus;
use App\Http\Controllers\Controller;
use App\Jobs\Scraping\ScrapeAppPageJob;
use App\Models\ShopifyApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminShopifyAppController extends Controller
{
    public function index(Request $request): Response
    {
        $query = $request->boolean('show_unlisted')
            ? ShopifyApp::withUnlisted()
            : ShopifyApp::query();

        $query->with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->input('search') . '%')
                  ->orWhere('developer_name', 'like', '%' . $request->input('search') . '%');
            });
        }

        $apps = $query->orderBy('name')->paginate(20)->withQueryString();

        return Inertia::render('Admin/ShopifyApps/Index', [
            'apps'    => $apps,
            'filters' => $request->only(['search', 'category_id', 'show_unlisted']),
            'pendingCount' => ShopifyApp::where('scraping_status', ScrapingStatus::Pending->value)->count(),
        ]);
    }

    public function show(Request $request, int $shopifyApp): Response
    {
        $app = ShopifyApp::withUnlisted()->with('category')->findOrFail($shopifyApp);

        $reviewQuery = $app->reviews();

        if ($request->filled('rating')) {
            $reviewQuery->where('rating', (int) $request->input('rating'));
        }

        if ($request->filled('sentiment')) {
            $reviewQuery->where('ai_sentiment', $request->input('sentiment'));
        }

        $reviews = $reviewQuery
            ->orderBy('published_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $painPoints = DB::table('review_pain_point')
            ->join('ai_pain_points', 'ai_pain_points.id', '=', 'review_pain_point.pain_point_id')
            ->join('store_reviews', 'store_reviews.id', '=', 'review_pain_point.review_id')
            ->where('store_reviews.shopify_app_id', $app->id)
            ->selectRaw('ai_pain_points.id, ai_pain_points.name, ai_pain_points.slug, ai_pain_points.category, COUNT(*) as occurrences')
            ->groupBy('ai_pain_points.id', 'ai_pain_points.name', 'ai_pain_points.slug', 'ai_pain_points.category')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(20)
            ->get();

        $sentimentBreakdown = $app->reviews()
            ->whereNotNull('ai_sentiment')
            ->selectRaw('ai_sentiment, COUNT(*) as count')
            ->groupBy('ai_sentiment')
            ->pluck('count', 'ai_sentiment');

        $ratingDistribution = $app->reviews()
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->orderBy('rating')
            ->pluck('count', 'rating');

        $reviewTimeline = $app->reviews()
            ->where('published_at', '>=', now()->subMonths(12))
            ->selectRaw("DATE_FORMAT(published_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        return Inertia::render('Admin/ShopifyApps/Show', [
            'app' => $app,
            'reviews' => $reviews,
            'painPoints' => $painPoints,
            'filters' => $request->only(['rating', 'sentiment']),
            'charts' => [
                'sentimentBreakdown' => $sentimentBreakdown,
                'ratingDistribution' => $ratingDistribution,
                'reviewTimeline' => $reviewTimeline,
            ],
        ]);
    }

    public function scrape(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);

        if (!empty($ids)) {
            $handles = ShopifyApp::whereIn('id', $ids)->pluck('shopify_app_handle');
        } else {
            $handles = ShopifyApp::where('scraping_status', ScrapingStatus::Pending->value)
                ->orWhere('scraping_status', ScrapingStatus::Error->value)
                ->pluck('shopify_app_handle');
        }

        if ($handles->isEmpty()) {
            return back()->with('success', 'No apps to scrape.');
        }

        $delaySeconds = 0;
        $handles->chunk(200)->each(function ($chunk) use (&$delaySeconds) {
            foreach ($chunk as $handle) {
                ScrapeAppPageJob::dispatch($handle)->delay(now()->addSeconds($delaySeconds));
            }
            $delaySeconds += 60;
        });

        return back()->with('success', "Enqueued {$handles->count()} apps for scraping in chunks of 200.");
    }

    public function unlist(int $shopifyApp): RedirectResponse
    {
        $app = ShopifyApp::withUnlisted()->findOrFail($shopifyApp);
        $app->unlist();

        return redirect()->back()->with('success', "App \"{$app->name}\" has been unlisted.");
    }

    public function relist(int $shopifyApp): RedirectResponse
    {
        $app = ShopifyApp::withUnlisted()->findOrFail($shopifyApp);
        $app->relist();

        return redirect()->back()->with('success', "App \"{$app->name}\" has been relisted.");
    }
}
