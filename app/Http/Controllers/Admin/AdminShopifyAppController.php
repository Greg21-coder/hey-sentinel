<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ScrapingStatus;
use App\Http\Controllers\Controller;
use App\Jobs\Scraping\ScrapeAppPageJob;
use App\Models\ShopifyApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function show(int $shopifyApp): Response
    {
        $app = ShopifyApp::withUnlisted()->with('category')->findOrFail($shopifyApp);

        $reviews = $app->reviews()
            ->orderBy('published_at', 'desc')
            ->paginate(15);

        $painPoints = \Illuminate\Support\Facades\DB::table('review_pain_point')
            ->join('ai_pain_points', 'ai_pain_points.id', '=', 'review_pain_point.pain_point_id')
            ->join('store_reviews', 'store_reviews.id', '=', 'review_pain_point.review_id')
            ->where('store_reviews.shopify_app_id', $app->id)
            ->selectRaw('ai_pain_points.id, ai_pain_points.name, ai_pain_points.slug, ai_pain_points.category, COUNT(*) as occurrences')
            ->groupBy('ai_pain_points.id', 'ai_pain_points.name', 'ai_pain_points.slug', 'ai_pain_points.category')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(20)
            ->get();

        return Inertia::render('Admin/ShopifyApps/Show', [
            'app'        => $app,
            'reviews'    => $reviews,
            'painPoints' => $painPoints,
        ]);
    }

    public function scrape(): RedirectResponse
    {
        $pending = ShopifyApp::where('scraping_status', ScrapingStatus::Pending->value)
            ->orWhere('scraping_status', ScrapingStatus::Error->value)
            ->pluck('shopify_app_handle');

        if ($pending->isEmpty()) {
            return back()->with('success', 'No pending apps to scrape.');
        }

        $delaySeconds = 0;
        $pending->chunk(200)->each(function ($chunk) use (&$delaySeconds) {
            foreach ($chunk as $handle) {
                ScrapeAppPageJob::dispatch($handle)->delay(now()->addSeconds($delaySeconds));
            }
            $delaySeconds += 60;
        });

        return back()->with('success', "Enqueued {$pending->count()} apps for scraping in chunks of 200.");
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
