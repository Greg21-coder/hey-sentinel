<?php

namespace App\Http\Controllers\Customer;

use App\Enums\FollowedAppKind;
use App\Events\AppFollowed;
use App\Http\Controllers\Controller;
use App\Jobs\Scraping\ScrapeReviewPageJob;
use App\Models\AccountFollowedApp;
use App\Models\ShopifyApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class FollowAppController extends Controller
{
    public function store(Request $request, ShopifyApp $shopifyApp): RedirectResponse
    {
        $data = $request->validate([
            'kind' => ['nullable', Rule::enum(FollowedAppKind::class)],
        ]);

        $account = $request->user()->currentAccount;

        abort_unless($account !== null, 403, 'No account found.');
        abort_unless($account->canUse('apps_tracked'), 403, 'Your plan does not allow tracking more apps.');

        $kind = $data['kind'] ?? FollowedAppKind::Competitor->value;

        $pivot = AccountFollowedApp::withoutGlobalScope('account')
            ->firstOrCreate(
                [
                    'account_id'     => $account->id,
                    'shopify_app_id' => $shopifyApp->id,
                ],
                [
                    'kind'        => $kind,
                    'followed_at' => now(),
                ]
            );

        if ($pivot->wasRecentlyCreated) {
            $account->recordUsage('apps_tracked');
        }

        event(new AppFollowed($account, $shopifyApp->fresh()));

        return back()->with('success', "Now following {$shopifyApp->name}.");
    }

    public function destroy(Request $request, ShopifyApp $shopifyApp): RedirectResponse
    {
        $account = $request->user()->currentAccount;

        abort_unless($account !== null, 403, 'No account found.');

        AccountFollowedApp::withoutGlobalScope('account')
            ->where('account_id', $account->id)
            ->where('shopify_app_id', $shopifyApp->id)
            ->delete();

        return back()->with('success', "Unfollowed {$shopifyApp->name}.");
    }

    public function syncReviews(Request $request, ShopifyApp $shopifyApp): RedirectResponse
    {
        $account = $request->user()->currentAccount;
        abort_unless($account !== null, 403, 'No account found.');

        $follows = AccountFollowedApp::withoutGlobalScope('account')
            ->where('account_id', $account->id)
            ->where('shopify_app_id', $shopifyApp->id)
            ->exists();
        abort_unless($follows, 403, 'You are not following this app.');

        $key = "manual-sync-reviews:app:{$shopifyApp->id}";
        if (RateLimiter::tooManyAttempts($key, 1)) {
            return back()->with('error', 'Already syncing. Wait a few minutes before retrying.');
        }
        RateLimiter::hit($key, 300);

        $shopifyApp->forceFill(['reviews_sync_started_at' => now()])->save();

        $pages = (int) config('scraping.defaults.review_pages_per_app', 3);
        for ($p = 1; $p <= $pages; $p++) {
            ScrapeReviewPageJob::dispatch($shopifyApp->id, $p);
        }

        return back()->with('success', "Sync started. Reviews for {$shopifyApp->name} will refresh in a few minutes.");
    }
}
