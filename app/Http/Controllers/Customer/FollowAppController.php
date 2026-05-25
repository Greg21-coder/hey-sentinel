<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\AccountFollowedApp;
use App\Models\ShopifyApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FollowAppController extends Controller
{
    public function store(Request $request, ShopifyApp $shopifyApp): RedirectResponse
    {
        $account = $request->user()->currentAccount;

        abort_unless($account !== null, 403, 'No account found.');
        abort_unless($account->canUse('apps_tracked'), 403, 'Your plan does not allow tracking more apps.');

        AccountFollowedApp::withoutGlobalScope('account')
            ->firstOrCreate(
                [
                    'account_id'     => $account->id,
                    'shopify_app_id' => $shopifyApp->id,
                ],
                [
                    'kind'        => 'competitor',
                    'followed_at' => now(),
                ]
            );

        $account->recordUsage('apps_tracked');

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
}
