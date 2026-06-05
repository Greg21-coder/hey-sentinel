<?php

namespace App\Http\Requests\Customer;

use App\Enums\FollowedAppKind;
use App\Models\AccountFollowedApp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MyAppsVersusShowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $accountId = $this->user()->currentAccount?->id ?? 0;

        return [
            'mine' => [
                'required',
                'integer',
                Rule::exists('account_followed_apps', 'shopify_app_id')
                    ->where(fn ($q) => $q->where('account_id', $accountId)
                        ->where('kind', FollowedAppKind::Mine->value)),
            ],
            'competitors' => ['array', 'max:3'],
            'competitors.*' => [
                'integer',
                'distinct',
                Rule::exists('account_followed_apps', 'shopify_app_id')
                    ->where(fn ($q) => $q->where('account_id', $accountId)
                        ->where('kind', FollowedAppKind::Competitor->value)),
                Rule::notIn([(int) $this->input('mine')]),
            ],
        ];
    }

    public function competitorIds(): array
    {
        return collect($this->input('competitors', []))->map(fn ($id) => (int) $id)->all();
    }

    public function mineId(): int
    {
        return (int) $this->input('mine');
    }
}
