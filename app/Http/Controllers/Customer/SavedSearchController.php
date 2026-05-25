<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\SavedSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SavedSearchController extends Controller
{
    public function index(Request $request): Response
    {
        $accountId = $request->user()->currentAccount?->id ?? 0;

        $searches = SavedSearch::withoutGlobalScope('account')
            ->where('account_id', $accountId)
            ->orderByDesc('updated_at')
            ->get();

        return Inertia::render('Customer/SavedSearches', [
            'savedSearches' => $searches,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'filters' => ['required', 'array'],
        ]);

        $account = $request->user()->currentAccount;

        abort_unless($account !== null, 403, 'No account found.');
        abort_unless($account->canUse('saved_searches'), 403, 'Your plan does not allow more saved searches.');

        SavedSearch::create([
            'account_id'    => $account->id,
            'user_id'       => $request->user()->id,
            'name'          => $data['name'],
            'filters'       => $data['filters'],
            'notify_on_new' => false,
        ]);

        $account->recordUsage('saved_searches');

        return back()->with('success', 'Saved search created.');
    }

    public function update(Request $request, SavedSearch $savedSearch): RedirectResponse
    {
        $this->authorizeSearch($request, $savedSearch);

        $data = $request->validate([
            'name'          => ['sometimes', 'string', 'max:100'],
            'filters'       => ['sometimes', 'array'],
            'notify_on_new' => ['sometimes', 'boolean'],
        ]);

        $savedSearch->update($data);

        return back()->with('success', 'Saved search updated.');
    }

    public function destroy(Request $request, SavedSearch $savedSearch): RedirectResponse
    {
        $this->authorizeSearch($request, $savedSearch);

        $savedSearch->delete();

        return back()->with('success', 'Saved search deleted.');
    }

    private function authorizeSearch(Request $request, SavedSearch $savedSearch): void
    {
        $accountId = $request->user()->currentAccount?->id ?? 0;
        abort_unless((int) $savedSearch->account_id === $accountId, 403);
    }
}
