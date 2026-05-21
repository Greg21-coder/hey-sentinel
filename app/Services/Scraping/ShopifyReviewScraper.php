<?php

namespace App\Services\Scraping;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ShopifyReviewScraper
{
    public function __construct(protected WebshareProxyManager $proxies) {}

    public function fetch(string $handle, int $page = 1): ?Response
    {
        $url = "https://apps.shopify.com/{$handle}/reviews?page={$page}";

        $userAgents = config('scraping.user_agents');
        $request = Http::withHeaders([
            'User-Agent' => $userAgents[array_rand($userAgents)],
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.9',
        ])->timeout(30);

        $proxy = $this->proxies->acquire();
        if ($proxy !== null) {
            $request = $request->withOptions(['proxy' => $this->buildProxyUrl($proxy)]);
        }

        $min = (int) config('scraping.defaults.jitter_min_ms');
        $max = (int) config('scraping.defaults.jitter_max_ms');
        usleep(random_int($min, $max) * 1000);

        $response = $request->get($url);

        if (in_array($response->status(), [429, 503], true) && $proxy !== null) {
            $this->proxies->cooldown($proxy);

            return null;
        }

        return $response;
    }

    protected function buildProxyUrl(string $proxy): string
    {
        [$user, $pass, $host, $port] = explode(':', $proxy);

        return "http://{$user}:{$pass}@{$host}:{$port}";
    }
}
