<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountStatus;
use App\Enums\BillingCycle;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountUser;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredAccountController extends Controller
{
    public function create(): Response
    {
        $plan = request('plan');

        return Inertia::render('Auth/Register', [
            'preselectedPlan' => $plan,
            'plans' => Plan::query()->public()->active()->orderBy('sort_order')->get(['id', 'slug', 'name', 'monthly_price']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'company_name' => ['required', 'string', 'max:100'],
            'plan_slug' => ['required', 'string', 'exists:plans,slug'],
        ]);

        $plan = Plan::where('slug', $data['plan_slug'])->firstOrFail();

        DB::transaction(function () use ($data, $plan) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'email_verified_at' => now(),
            ]);

            $account = Account::create([
                'name' => $data['company_name'],
                'slug' => Str::slug($data['company_name']).'-'.Str::random(6),
                'status' => AccountStatus::Trial->value,
                'plan_id' => $plan->id,
                'billing_cycle' => BillingCycle::None->value,
                'trial_started_at' => now(),
                'free_trial_ends_at' => now()->addDays(14),
                'owner_user_id' => $user->id,
            ]);

            AccountUser::create([
                'account_id' => $account->id,
                'user_id' => $user->id,
                'role' => UserRole::Owner->value,
                'invitation_accepted_at' => now(),
            ]);
        });

        return redirect()->to('/login')->with('success', 'Account created! Please log in.');
    }
}
