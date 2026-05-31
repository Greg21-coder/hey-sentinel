<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\AppChangeNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function unreadCount(Request $request): JsonResponse
    {
        $accountId = $request->user()->currentAccount?->id ?? 0;

        $count = AppChangeNotification::where('account_id', $accountId)
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    public function recent(Request $request): JsonResponse
    {
        $accountId = $request->user()->currentAccount?->id ?? 0;

        $notifications = AppChangeNotification::where('account_id', $accountId)
            ->whereNull('read_at')
            ->with(['change.app:id,name,shopify_app_handle'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'app_name' => $n->change?->app?->name ?? 'Unknown',
                'app_id' => $n->change?->app?->id,
                'field' => $n->change?->field,
                'old_value' => $n->change?->old_value,
                'new_value' => $n->change?->new_value,
                'created_at' => $n->created_at?->toISOString(),
            ]);

        return response()->json(['notifications' => $notifications]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $accountId = $request->user()->currentAccount?->id ?? 0;

        AppChangeNotification::where('account_id', $accountId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }
}
