<?php

namespace App\Services\Scraping;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Scrapes the public app listing page on apps.shopify.com.
 * Returns a normalized array; persistence is the caller's responsibility.
 */
class ShopifyAppScraper
{
    public function __construct(protected WebshareProxyManager $proxies) {}

    public function fetch(string $handle): ?Response
    {
        $userAgent = $this->randomUserAgent();
        $url = "https://apps.shopify.com/{$handle}";

        $request = Http::withHeaders([
            'User-Agent' => $userAgent,
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.9',
        ])->timeout(30);

        $proxy = $this->proxies->acquire();
        if ($proxy !== null) {
            $request = $request->withOptions(['proxy' => $this->buildProxyUrl($proxy)]);
        }

        $this->applyJitter();

        $response = $request->get($url);

        if ($response->status() === 429 || $response->status() === 503) {
            if ($proxy !== null) {
                $this->proxies->cooldown($proxy);
            }

            return null;
        }

        return $response;
    }

    protected function randomUserAgent(): string
    {
        $agents = config('scraping.user_agents');

        return $agents[array_rand($agents)];
    }

    protected function applyJitter(): void
    {
        $min = (int) config('scraping.defaults.jitter_min_ms');
        $max = (int) config('scraping.defaults.jitter_max_ms');
        usleep(random_int($min, $max) * 1000);
    }

    protected function buildProxyUrl(string $proxy): string
    {
        [$user, $pass, $host, $port] = explode(':', $proxy);

        return "http://{$user}:{$pass}@{$host}:{$port}";
    }
}
