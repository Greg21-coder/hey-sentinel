<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopifyApp;
use App\Models\StoreReview;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminStoreReviewController extends Controller
{
    public function index(Request $request): Response
    {
        $query = StoreReview::with('app');

        if ($request->filled('shopify_app_id')) {
            $query->where('shopify_app_id', $request->input('shopify_app_id'));
        }

        if ($request->filled('rating')) {
            $query->where('rating', $request->input('rating'));
        }

        if ($request->filled('ai_sentiment')) {
            $query->where('ai_sentiment', $request->input('ai_sentiment'));
        }

        if ($request->filled('ai_status')) {
            $query->where('ai_status', $request->input('ai_status'));
        }

        $reviews = $query->orderBy('published_at', 'desc')->paginate(20)->withQueryString();

        $apps = ShopifyApp::withUnlisted()
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Admin/StoreReviews/Index', [
            'reviews' => $reviews,
            'apps'    => $apps,
            'filters' => $request->only(['shopify_app_id', 'rating', 'ai_sentiment', 'ai_status']),
        ]);
    }
}
