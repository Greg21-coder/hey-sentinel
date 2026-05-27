<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppChange;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminAppChangeController extends Controller
{
    public function index(Request $request): Response
    {
        $query = AppChange::query()->with('app:id,name,shopify_app_handle');

        if ($request->filled('field')) {
            $query->where('field', $request->input('field'));
        }

        if ($request->filled('search')) {
            $query->whereHas('app', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->input('search') . '%');
            });
        }

        $changes = $query->orderByDesc('detected_at')->paginate(20)->withQueryString();

        return Inertia::render('Admin/AppChanges/Index', [
            'changes' => $changes,
            'filters' => $request->only(['field', 'search']),
        ]);
    }
}
