<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminPlanController extends Controller
{
    public function index(Request $request): Response
    {
        $plans = Plan::withTrashed()
            ->withCount('accounts')
            ->orderBy('sort_order')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Plans/Index', [
            'plans' => $plans,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Plans/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slug'          => ['required', 'string', 'max:255', 'unique:plans,slug'],
            'name'          => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'yearly_price'  => ['required', 'numeric', 'min:0'],
            'sort_order'    => ['required', 'integer', 'min:0'],
            'status'        => ['required', 'string', 'in:draft,active,deprecated'],
            'is_public'     => ['boolean'],
        ]);

        Plan::create($validated);

        return redirect()->route('admin.plans.index')->with('success', 'Plan created.');
    }

    public function edit(Plan $plan): Response
    {
        return Inertia::render('Admin/Plans/Edit', [
            'plan'     => $plan->load('features'),
            'features' => $plan->features,
        ]);
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'slug'          => ['required', 'string', 'max:255', 'unique:plans,slug,' . $plan->id],
            'name'          => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'yearly_price'  => ['required', 'numeric', 'min:0'],
            'sort_order'    => ['required', 'integer', 'min:0'],
            'status'        => ['required', 'string', 'in:draft,active,deprecated'],
            'is_public'     => ['boolean'],
        ]);

        $plan->update($validated);

        return redirect()->route('admin.plans.index')->with('success', 'Plan updated.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->accounts()->count() > 0) {
            return redirect()->back()->with('error', 'Cannot delete a plan that has active accounts.');
        }

        $plan->delete();

        return redirect()->route('admin.plans.index')->with('success', 'Plan deleted.');
    }
}
