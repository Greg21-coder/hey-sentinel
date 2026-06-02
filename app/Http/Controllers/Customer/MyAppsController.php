<?php

namespace App\Http\Controllers\Customer;

use App\Enums\FollowedAppKind;
use App\Http\Controllers\Controller;
use App\Models\ShopifyApp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MyAppsController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $accountId = $request->user()->currentAccount?->id ?? 0;

        $results = ShopifyApp::query()
            ->where(function ($w) use ($q) {
                $w->where('name', 'LIKE', "%{$q}%")
                  ->orWhere('shopify_app_handle', 'LIKE', "%{$q}%");
            })
            ->whereNotIn('id', function ($sub) use ($accountId) {
                $sub->select('shopify_app_id')
                    ->from('account_followed_apps')
                    ->where('account_id', $accountId)
                    ->where('kind', FollowedAppKind::Mine->value);
            })
            ->whereNull('unlisted_at')
            ->orderByRaw("CASE WHEN scraping_status = 'scraped' THEN 0 ELSE 1 END")
            ->orderByDesc('total_reviews')
            ->limit(10)
            ->get(['id', 'shopify_app_handle', 'name', 'avatar_url', 'scraping_status', 'average_rating', 'total_reviews']);

        return response()->json(['results' => $results]);
    }
}
