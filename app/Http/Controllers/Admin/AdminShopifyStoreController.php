<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopifyStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminShopifyStoreController extends Controller
{
    public function index(Request $request): Response
    {
        $query = $request->boolean('show_unlisted')
            ? ShopifyStore::withUnlisted()
            : ShopifyStore::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('domain', 'like', '%' . $request->input('search') . '%')
                  ->orWhere('store_name', 'like', '%' . $request->input('search') . '%');
            });
        }

        $stores = $query->orderBy('domain')->paginate(20)->withQueryString();

        return Inertia::render('Admin/ShopifyStores/Index', [
            'stores'  => $stores,
            'filters' => $request->only(['search', 'show_unlisted']),
        ]);
    }

    public function unlist(int $shopifyStore): RedirectResponse
    {
        $store = ShopifyStore::withUnlisted()->findOrFail($shopifyStore);
        $store->unlist();

        return redirect()->back()->with('success', "Store \"{$store->domain}\" has been unlisted.");
    }

    public function relist(int $shopifyStore): RedirectResponse
    {
        $store = ShopifyStore::withUnlisted()->findOrFail($shopifyStore);
        $store->relist();

        return redirect()->back()->with('success', "Store \"{$store->domain}\" has been relisted.");
    }
}
