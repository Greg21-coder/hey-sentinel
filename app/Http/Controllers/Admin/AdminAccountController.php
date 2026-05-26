<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminAccountController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Account::withTrashed()
            ->with(['owner', 'plan'])
            ->withCount('users');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        $accounts = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return Inertia::render('Admin/Accounts/Index', [
            'accounts'       => $accounts,
            'filters'        => $request->only(['status', 'search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Accounts/Create', [
            'plans' => Plan::orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'slug'          => ['nullable', 'string', 'max:255', 'unique:accounts,slug'],
            'status'        => ['required', 'string', 'in:trial,active,past_due,suspended,cancelled'],
            'plan_id'       => ['nullable', 'integer', 'exists:plans,id'],
            'billing_cycle' => ['required', 'string', 'in:monthly,yearly,none'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $validated['uuid'] = (string) Str::uuid();

        Account::create($validated);

        return redirect()->route('admin.accounts.index')->with('success', 'Account created.');
    }

    public function edit(Account $account): Response
    {
        return Inertia::render('Admin/Accounts/Edit', [
            'account' => $account->load(['owner', 'plan']),
            'plans'   => Plan::orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, Account $account): RedirectResponse
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'slug'          => ['nullable', 'string', 'max:255', 'unique:accounts,slug,' . $account->id],
            'status'        => ['required', 'string', 'in:trial,active,past_due,suspended,cancelled'],
            'plan_id'       => ['nullable', 'integer', 'exists:plans,id'],
            'billing_cycle' => ['required', 'string', 'in:monthly,yearly,none'],
        ]);

        $account->update($validated);

        return redirect()->route('admin.accounts.index')->with('success', 'Account updated.');
    }

    public function destroy(Account $account): RedirectResponse
    {
        if ($account->status === 'active') {
            return redirect()->back()->with('error', 'Cannot delete an account with an active subscription.');
        }

        $account->delete();

        return redirect()->route('admin.accounts.index')->with('success', 'Account deleted.');
    }
}
