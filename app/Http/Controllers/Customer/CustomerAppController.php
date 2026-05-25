<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\AccountFollowedApp;
use App\Models\ShopifyApp;
use App\Models\ShopifyAppCategory;
use App\Models\AiPainPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CustomerAppController extends Controller
{
    public function index(Request $request): Response
    {
        $accountId = $request->user()->currentAccount?->id ?? 0;

        $filters = [
            'category_id' => $request->integer('category_id') ?: null,
            'rating_min'  => $request->filled('rating_min') ? (float) $request->input('rating_min') : null,
            'pricing'     => $request->input('pricing', 'all'), // free | paid | all
            'keyword'     => $request->input('keyword'),
        ];

        $query = ShopifyApp::with('category')
            ->when($filters['category_id'], fn ($q) => $q->where('category_id', $filters['category_id']))
            ->when($filters['rating_min'] !== null, fn ($q) => $q->where('average_rating', '>=', $filters['rating_min']))
            ->when($filters['pricing'] === 'free', fn ($q) => $q->where('pricing_has_free', true))
            ->when($filters['pricing'] === 'paid', fn ($q) => $q->where('pricing_has_free', false))
            ->when($filters['keyword'], fn ($q) => $q->where(function ($inner) use ($filters) {
                $inner->where('name', 'LIKE', "%{$filters['keyword']}%")
                      ->orWhere('description', 'LIKE', "%{$filters['keyword']}%");
            }))
            ->orderByDesc('total_reviews');

        $apps = $query->paginate(20)->withQueryString();

        $followedIds = AccountFollowedApp::withoutGlobalScope('account')
            ->where('account_id', $accountId)
            ->pluck('shopify_app_id')
            ->toArray();

        $categories = ShopifyAppCategory::orderBy('name')->get(['id', 'name']);

        $painPointOptions = AiPainPoint::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Customer/Apps/Index', [
            'apps'             => $apps,
            'followedIds'      => $followedIds,
            'categories'       => $categories,
            'painPointOptions' => $painPointOptions,
            'filters'          => $filters,
        ]);
    }

    public function show(Request $request, ShopifyApp $shopifyApp): Response
    {
        $accountId = $request->user()->currentAccount?->id ?? 0;

        $shopifyApp->load('category');

        $isFollowed = AccountFollowedApp::withoutGlobalScope('account')
            ->where('account_id', $accountId)
            ->where('shopify_app_id', $shopifyApp->id)
            ->exists();

        $reviews = $shopifyApp->reviews()
            ->orderByDesc('published_at')
            ->paginate(15)
            ->withQueryString();

        $painPoints = DB::table('review_pain_point')
            ->join('store_reviews', 'store_reviews.id', '=', 'review_pain_point.review_id')
            ->join('ai_pain_points', 'ai_pain_points.id', '=', 'review_pain_point.pain_point_id')
            ->where('store_reviews.shopify_app_id', $shopifyApp->id)
            ->selectRaw('ai_pain_points.id, ai_pain_points.name, ai_pain_points.category, COUNT(*) as mention_count')
            ->groupBy('ai_pain_points.id', 'ai_pain_points.name', 'ai_pain_points.category')
            ->orderByDesc('mention_count')
            ->limit(20)
            ->get();

        return Inertia::render('Customer/Apps/Show', [
            'app'        => $shopifyApp,
            'isFollowed' => $isFollowed,
            'reviews'    => $reviews,
            'painPoints' => $painPoints,
        ]);
    }
}
