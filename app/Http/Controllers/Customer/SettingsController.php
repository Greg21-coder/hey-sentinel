<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        $user    = $request->user();
        $account = $user->currentAccount;

        return Inertia::render('Customer/Settings', [
            'account' => $account !== null ? [
                'name'        => $account->name,
                'plan_name'   => $account->plan?->name,
                'status'      => $account->status?->value,
                'trial_ends'  => $account->free_trial_ends_at?->toDateString(),
            ] : null,
            'user' => [
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name'                  => ['sometimes', 'string', 'max:100'],
            'email'                 => ['sometimes', 'email:rfc', 'max:255', 'unique:users,email,'.$user->id],
            'password'              => ['sometimes', 'confirmed', Password::defaults()],
        ]);

        if (isset($data['name'])) {
            $user->name = $data['name'];
        }

        if (isset($data['email'])) {
            $user->email = $data['email'];
        }

        if (isset($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return back()->with('success', 'Settings updated.');
    }
}
