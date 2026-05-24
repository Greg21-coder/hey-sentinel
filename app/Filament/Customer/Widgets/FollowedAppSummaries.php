<?php

namespace App\Filament\Customer\Widgets;

use App\Models\AccountFollowedApp;
use App\Models\ShopifyApp;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class FollowedAppSummaries extends Widget
{
    protected string $view = 'filament.customer.widgets.followed-app-summaries';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return Collection<int, ShopifyApp>
     */
    protected function getApps(): Collection
    {
        // Explicit account filter — defense in depth, same pattern as the
        // other tenant-scoped customer widgets.
        $accountId = Auth::user()?->currentAccount?->id ?? 0;

        $appIds = AccountFollowedApp::query()
            ->where('account_id', $accountId)
            ->pluck('shopify_app_id');

        if ($appIds->isEmpty()) {
            return collect();
        }

        return ShopifyApp::query()
            ->whereIn('id', $appIds)
            ->whereNotNull('ai_summary')
            ->orderByDesc('ai_summary_at')
            ->get();
    }

    protected function getViewData(): array
    {
        return ['apps' => $this->getApps()];
    }
}
