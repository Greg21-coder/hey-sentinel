<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\ShopifyApp;
use App\Models\ShopifyStore;
use App\Models\StoreReview;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function __invoke(): Response
    {
        $stats = Cache::remember('landing_stats', 3600, function () {
            return [
                'apps_count'    => ShopifyApp::count(),
                'stores_count'  => ShopifyStore::count(),
                'reviews_count' => StoreReview::count(),
            ];
        });

        $plans = Plan::query()
            ->public()
            ->active()
            ->with('features')
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($plan) => [
                'id'            => $plan->id,
                'slug'          => $plan->slug,
                'name'          => $plan->name,
                'description'   => $plan->description,
                'monthly_price' => $plan->monthly_price,
                'features'      => $plan->features->map(fn ($f) => [
                    'feature_key'   => $f->feature_key,
                    'feature_value' => $f->feature_value,
                    'value_type'    => $f->value_type,
                ])->toArray(),
            ])
            ->toArray();

        return Inertia::render('Landing', [
            'stats' => $stats,
            'plans' => $plans,
        ]);
    }
}
