<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class MyAppsStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->currentAccount !== null;
    }

    public function rules(): array
    {
        return [
            'handle' => ['required_without:url', 'nullable', 'string', 'max:200', 'regex:/^[a-z0-9][a-z0-9\-]*$/'],
            'url' => ['required_without:handle', 'nullable', 'string', 'max:500', 'regex:#^https://apps\.shopify\.com/[a-z0-9][a-z0-9\-]*/?$#'],
        ];
    }

    public function messages(): array
    {
        return [
            'url.regex' => 'The url must be a Shopify app URL (https://apps.shopify.com/<handle>).',
            'handle.regex' => 'The handle format is invalid.',
        ];
    }

    /** Returns the canonical handle from either `handle` or `url`. */
    public function resolveHandle(): string
    {
        if ($this->filled('handle')) {
            return $this->input('handle');
        }

        $url = rtrim($this->input('url'), '/');
        $segments = explode('/', parse_url($url, PHP_URL_PATH) ?: '');

        return end($segments);
    }
}
