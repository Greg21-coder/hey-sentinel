<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\MyAppsVersusShowRequest;
use App\Jobs\Ai\ExtractAppFeaturesJob;
use App\Models\ShopifyApp;
use App\Services\Versus\ComparisonAssemblerService;
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
}
