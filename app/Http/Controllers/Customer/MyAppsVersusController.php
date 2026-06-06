<?php

namespace App\Http\Controllers\Customer;

use App\Enums\FollowedAppKind;
use App\Exceptions\Ai\VersusSummaryFailed;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\MyAppsVersusSummarizeRequest;
use App\Http\Requests\Customer\MyAppsVersusShowRequest;
use App\Jobs\Ai\ExtractAppFeaturesJob;
use App\Models\AccountFollowedApp;
use App\Models\ShopifyApp;
use App\Services\Ai\VersusSummaryService;
use App\Services\Versus\ComparisonAssemblerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MyAppsVersusController extends Controller
{
    public function show(MyAppsVersusShowRequest $request, ComparisonAssemblerService $assembler): InertiaResponse|RedirectResponse
    {
        $accountId = $request->user()->currentAccount->id;
        $mineId = $request->mineId();

        if ($mineId === null) {
            $firstMineId = AccountFollowedApp::withoutGlobalScope('account')
                ->where('account_id', $accountId)
                ->where('kind', FollowedAppKind::Mine->value)
                ->orderBy('followed_at')
                ->value('shopify_app_id');

            if ($firstMineId === null) {
                return redirect('/customer/my-apps')
                    ->with('error', 'Add an app to My Apps first to start comparing.');
            }

            return redirect('/customer/my-apps/versus?mine='.$firstMineId);
        }

        $mine = ShopifyApp::findOrFail($mineId);
        $competitors = ShopifyApp::whereIn('id', $request->competitorIds())->get();

        foreach (collect([$mine])->concat($competitors) as $app) {
            if ($app->features_json === null) {
                ExtractAppFeaturesJob::dispatch($app->id);
            }
        }

        $versus = $assembler->assemble($mine, $competitors, $accountId);

        $mineOptions = ShopifyApp::query()
            ->whereIn('id', function ($sub) use ($accountId) {
                $sub->select('shopify_app_id')->from('account_followed_apps')
                    ->where('account_id', $accountId)
                    ->where('kind', \App\Enums\FollowedAppKind::Mine->value);
            })
            ->get(['id', 'name'])->all();

        $competitorOptions = ShopifyApp::query()
            ->whereIn('id', function ($sub) use ($accountId) {
                $sub->select('shopify_app_id')->from('account_followed_apps')
                    ->where('account_id', $accountId)
                    ->where('kind', \App\Enums\FollowedAppKind::Competitor->value);
            })
            ->get(['id', 'name'])->all();

        return Inertia::render('Customer/MyAppsVersus', [
            'versus' => $versus,
            'mineSelection' => $mine->id,
            'competitorSelection' => $competitors->pluck('id')->all(),
            'mineOptions' => $mineOptions,
            'competitorOptions' => $competitorOptions,
        ]);
    }

    public function summarize(
        MyAppsVersusSummarizeRequest $request,
        ComparisonAssemblerService $assembler,
        VersusSummaryService $summaryService,
    ): RedirectResponse {
        $accountId = $request->user()->currentAccount->id;
        $key = "versus-summary:account:{$accountId}";

        if (! RateLimiter::attempt($key, 1, fn () => null, 30)) {
            abort(429, 'Wait 30 seconds before regenerating.');
        }

        $mine = ShopifyApp::findOrFail($request->mineId());
        $competitors = ShopifyApp::whereIn('id', $request->competitorIds())->get();
        $payload = $assembler->assemble($mine, $competitors, $accountId);

        try {
            $summaryService->generate($accountId, $mine, $competitors, $payload);
        } catch (VersusSummaryFailed $e) {
            Log::channel('ai')->error('versus.summary_failed', [
                'account_id' => $accountId,
                'mine_app_id' => $mine->id,
                'competitor_ids' => $competitors->pluck('id')->all(),
                'message' => $e->getMessage(),
            ]);
            abort(503, "Couldn't generate the analysis. Please try again shortly.");
        }

        return redirect()->to(url()->previous() ?: '/customer/my-apps/versus?mine='.$mine->id.'&'.http_build_query(['competitors' => $competitors->pluck('id')->all()]));
    }
}
