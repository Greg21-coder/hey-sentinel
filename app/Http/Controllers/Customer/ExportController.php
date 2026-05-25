<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Jobs\ExportCsvJob;
use App\Models\ShopifyApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function apps(Request $request): RedirectResponse
    {
        $account = $request->user()->currentAccount;

        abort_unless($account !== null, 403, 'No account found.');
        abort_unless($account->canUse('export_csv'), 403, 'Your plan does not include CSV export.');

        $filters = $request->only(['category_id', 'rating_min', 'pricing', 'keyword']);

        ExportCsvJob::dispatch($account->id, 'apps', $filters);

        return back()->with('success', 'App export queued. You will receive it shortly.');
    }

    public function reviews(Request $request, ShopifyApp $shopifyApp): RedirectResponse
    {
        $account = $request->user()->currentAccount;

        abort_unless($account !== null, 403, 'No account found.');
        abort_unless($account->canUse('export_csv'), 403, 'Your plan does not include CSV export.');

        ExportCsvJob::dispatch($account->id, 'reviews', ['shopify_app_id' => $shopifyApp->id]);

        return back()->with('success', "Review export for {$shopifyApp->name} queued.");
    }
}
