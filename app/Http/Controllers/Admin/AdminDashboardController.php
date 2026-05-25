<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiBatch;
use App\Models\Account;
use App\Models\ShopifyApp;
use App\Models\StoreReview;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $accountsByStatus = Account::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $aiBatchesByStatus = AiBatch::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'accounts'           => $accountsByStatus,
                'total_accounts'     => array_sum($accountsByStatus),
                'total_users'        => User::count(),
                'total_apps'         => ShopifyApp::withUnlisted()->count(),
                'total_processed'    => StoreReview::where('ai_status', 'processed')->count(),
                'total_pending'      => StoreReview::where('ai_status', 'pending')->count(),
                'ai_batches'         => $aiBatchesByStatus,
                'last_scraped_at'    => ShopifyApp::withUnlisted()->max('last_scraped_at'),
            ],
        ]);
    }
}
