<?php

namespace App\Services\Scraping;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class StoreleadsClient
{
    public function client(): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer '.config('scraping.storeleads.api_key'),
            'Accept' => 'application/json',
        ])->baseUrl(config('scraping.storeleads.base_url'))
            ->timeout(30)
            ->retry(2, 1000);
    }

    /** Fetch a single store record by domain. */
    public function getStore(string $domain): ?array
    {
        $response = $this->client()->get("/store/{$domain}");

        return $response->successful() ? $response->json() : null;
    }

    /** Fetch a chunk of stores by their Storeleads ids. */
    public function getStoresByIds(array $ids): array
    {
        $response = $this->client()->post('/stores/batch', ['ids' => $ids]);

        return $response->successful() ? ($response->json('results') ?? []) : [];
    }
}
