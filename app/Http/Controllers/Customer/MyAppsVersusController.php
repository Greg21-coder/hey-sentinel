<?php

namespace App\Http\Controllers\Customer;

use App\Exceptions\Ai\VersusSummaryFailed;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\MyAppsVersusSummarizeRequest;
use App\Http\Requests\Customer\MyAppsVersusShowRequest;
use App\Jobs\Ai\ExtractAppFeaturesJob;
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
    public function show(MyAppsVersusShowRequest $request, ComparisonAssemblerService $assembler): InertiaResponse
    {
        $accountId = $request->user()->currentAccount->id;
        $mine = ShopifyApp::findOrFail($request->mineId());
        $competitors = ShopifyApp::whereIn('id', $request->competitorIds())->get();

        foreach (collect([$mine])->concat($competitors) as $app) {
            if ($app->features_json === null) {
                ExtractAppFeaturesJob::dispatch($app->id);
            }
        }

        $versus = $assembler->assemble($mine, $competitors, $accountId);

        return Inertia::render('Customer/MyAppsVersus', [
            'versus' => $versus,
            'mineSelection' => $mine->id,
            'competitorSelection' => $competitors->pluck('id')->all(),
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
            abort(429, 'Espera 30 segundos antes de regenerar.');
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
            abort(503, 'No pudimos generar el análisis. Intenta de nuevo en un momento.');
        }

        return redirect()->to(url()->previous() ?: '/customer/my-apps/versus?mine='.$mine->id.'&'.http_build_query(['competitors' => $competitors->pluck('id')->all()]));
    }
}
