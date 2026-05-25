<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user();
        $account = $user?->currentAccount;

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_super_admin' => $user->isSuperAdmin(),
                ] : null,
                'account' => $account ? [
                    'id' => $account->id,
                    'name' => $account->name,
                    'slug' => $account->slug,
                    'status' => $account->status->value,
                    'plan' => [
                        'id' => $account->plan->id,
                        'name' => $account->plan->name,
                        'slug' => $account->plan->slug,
                    ],
                ] : null,
            ],
            'featureGates' => $account ? $this->buildFeatureGates($account) : [],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ]);
    }

    protected function buildFeatureGates($account): array
    {
        $plan = $account->plan;
        $gates = [];

        foreach ($plan->features as $feature) {
            $gates[$feature->feature_key] = [
                'value' => $feature->castedValue(),
                'type' => $feature->value_type->value,
                'usage' => in_array($feature->value_type->value, ['integer'])
                    ? $account->usageThisPeriod($feature->feature_key)
                    : null,
            ];
        }

        return $gates;
    }
}
