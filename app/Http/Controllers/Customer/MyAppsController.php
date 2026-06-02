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
use Illuminate\Support\Facades\Log;

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
