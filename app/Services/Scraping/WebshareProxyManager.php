<?php

namespace App\Services\Scraping;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

/**
 * Manages a pool of Webshare.io proxies in Redis as a sorted set,
 * sorted by latency (smaller = faster). Cooldown removes a proxy
 * temporarily after a 429/503/Cloudflare block.
 */
class WebshareProxyManager
{
    public const POOL_KEY = 'scraping:webshare:pool';

    public const COOLDOWN_KEY_PREFIX = 'scraping:webshare:cooldown:';

    public function refreshPool(): int
    {
        $token = config('scraping.webshare.api_token');
        if (empty($token)) {
            return 0;
        }

        $response = Http::withToken($token, 'Token')
            ->acceptJson()
            ->get(config('scraping.webshare.proxy_list_url'), [
                'page_size' => config('scraping.webshare.max_proxies'),
            ]);

        if ($response->failed()) {
            return 0;
        }

        $results = $response->json('results', []);
        Redis::del(self::POOL_KEY);

        foreach ($results as $proxy) {
            $address = sprintf(
                '%s:%s:%s:%s',
                $proxy['username'] ?? '',
                $proxy['password'] ?? '',
                $proxy['proxy_address'] ?? '',
                $proxy['port'] ?? 0,
            );
            // Default latency score = 100ms; refined per request.
            Redis::zadd(self::POOL_KEY, 100, $address);
        }

        return count($results);
    }

    /** Pop the fastest available (not cooling-down) proxy. Returns null if none. */
    public function acquire(): ?string
    {
        $candidates = Redis::zrange(self::POOL_KEY, 0, 20);

        foreach ($candidates as $proxy) {
            if (! Redis::exists(self::COOLDOWN_KEY_PREFIX.md5($proxy))) {
                return $proxy;
            }
        }

        return null;
    }

    public function reportLatency(string $proxy, int $ms): void
    {
        Redis::zadd(self::POOL_KEY, $ms, $proxy);
    }

    public function cooldown(string $proxy, ?int $seconds = null): void
    {
        $seconds ??= (int) config('scraping.cooldown.proxy_after_block_seconds');
        Redis::setex(self::COOLDOWN_KEY_PREFIX.md5($proxy), $seconds, '1');
    }

    public function size(): int
    {
        return (int) Redis::zcard(self::POOL_KEY);
    }
}
