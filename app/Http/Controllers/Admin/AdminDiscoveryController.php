<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\Scraping\DiscoverAppsFromSitemapJob;
use App\Models\DiscoveryRun;
use App\Models\ShopifyApp;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdminDiscoveryController extends Controller
{
    public function index(): Response
    {
        $runs = DiscoveryRun::query()
            ->orderByDesc('created_at')
            ->paginate(15);

        $lastRun = DiscoveryRun::query()->latest()->first();

        return Inertia::render('Admin/Discovery/Index', [
            'runs' => $runs,
            'stats' => [
                'total_apps' => ShopifyApp::count(),
                'pending_scraping' => ShopifyApp::where('scraping_status', 'pending')->count(),
                'last_run' => $lastRun,
            ],
            'hasRunning' => DiscoveryRun::where('status', 'running')->exists(),
        ]);
    }

    public function run(): RedirectResponse
    {
        $limit = (int) config('scraping.discovery.default_limit');

        $run = DiscoveryRun::create([
            'source' => 'sitemap',
            'status' => 'pending',
            'triggered_by' => 'manual',
            'apps_limit' => $limit,
        ]);

        DiscoverAppsFromSitemapJob::dispatch($run);

        return back()->with('success', "Discovery run #{$run->id} enqueued.");
    }
}
