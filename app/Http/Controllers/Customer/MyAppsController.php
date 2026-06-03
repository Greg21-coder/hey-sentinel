<?php

namespace App\Http\Controllers\Customer;

use App\Enums\FollowedAppKind;
use App\Enums\ScrapingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\MyAppsStoreRequest;
use App\Models\AccountFollowedApp;
use App\Models\ShopifyApp;
use App\Services\Scraping\ShopifyAppImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MyAppsController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $accountId = $request->user()->currentAccount?->id ?? 0;

        $myApps = DB::table('account_followed_apps')
            ->join('shopify_apps', 'shopify_apps.id', '=', 'account_followed_apps.shopify_app_id')
            ->where('account_followed_apps.account_id', $accountId)
            ->where('account_followed_apps.kind', FollowedAppKind::Mine->value)
            ->whereNull('shopify_apps.unlisted_at')
            ->select([
                'shopify_apps.id',
                'shopify_apps.shopify_app_handle',
                'shopify_apps.name',
                'shopify_apps.avatar_url',
                'shopify_apps.average_rating',
                'shopify_apps.total_reviews',
                'shopify_apps.reviews_sync_started_at',
                'account_followed_apps.followed_at',
            ])
            ->selectSub(
                DB::table('store_reviews')
                    ->whereColumn('store_reviews.shopify_app_id', 'shopify_apps.id')
                    ->selectRaw('COUNT(*)'),
                'scraped_reviews_count'
            )
            ->orderBy('account_followed_apps.followed_at', 'desc')
            ->get()
            ->map(fn ($row) => array_merge((array) $row, [
                'scraped_reviews_count' => (int) $row->scraped_reviews_count,
            ]))
            ->values();

        $needsTour = $myApps->isEmpty();

        return Inertia::render('Customer/MyApps', [
            'myApps' => $myApps,
            'onboarding' => ['needsTour' => $needsTour],
        ]);
    }

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

    public function store(MyAppsStoreRequest $request, ShopifyAppImportService $importer): RedirectResponse
    {
        $handle = $request->resolveHandle();
        $account = $request->user()->currentAccount;

        abort_unless($account->canUse('apps_tracked'), 403, 'Your plan does not allow tracking more apps.');

        $app = ShopifyApp::firstOrCreate(
            ['shopify_app_handle' => $handle],
            [
                'name' => $handle,
                'developer_name' => 'pending-import',
                'scraping_status' => ScrapingStatus::Pending->value,
            ]
        );

        if ($app->scraping_status !== ScrapingStatus::Scraped) {
            try {
                $started = microtime(true);
                $app = $importer->importByHandle($handle);
                Log::info('MyApps import succeeded', [
                    'handle' => $handle,
                    'account_id' => $account->id,
                    'duration_ms' => (int) ((microtime(true) - $started) * 1000),
                ]);
            } catch (\RuntimeException $e) {
                Log::warning('MyApps import failed', [
                    'handle' => $handle,
                    'account_id' => $account->id,
                    'reason' => $e->getMessage(),
                ]);

                return redirect('/customer/my-apps')
                    ->with('error', "Couldn't import {$handle}: {$e->getMessage()}");
            }
        }

        $pivot = AccountFollowedApp::withoutGlobalScope('account')
            ->firstOrCreate(
                ['account_id' => $account->id, 'shopify_app_id' => $app->id],
                ['kind' => FollowedAppKind::Mine->value, 'followed_at' => now()]
            );

        if ($pivot->wasRecentlyCreated) {
            $account->recordUsage('apps_tracked');
        }

        return redirect('/customer/my-apps')->with('success', "{$app->name} added to your apps.");
    }
}
