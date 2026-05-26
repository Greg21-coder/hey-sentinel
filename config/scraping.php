<?php

return [

    'webshare' => [
        'api_token' => env('WEBSHARE_API_TOKEN'),
        'proxy_list_url' => env('WEBSHARE_PROXY_LIST_URL', 'https://proxy.webshare.io/api/v2/proxy/list/?mode=direct'),
        'max_proxies' => (int) env('WEBSHARE_MAX_PROXIES', 10),
    ],

    'storeleads' => [
        'api_key' => env('STORELEADS_API_KEY'),
        'base_url' => env('STORELEADS_BASE_URL', 'https://storeleads.app/json/api'),
    ],

    'discovery' => [
        'sitemap_url' => env('DISCOVERY_SITEMAP_URL', 'https://apps.shopify.com/sitemap_apps_en.xml'),
        'default_limit' => (int) env('DISCOVERY_DEFAULT_LIMIT', 500),
    ],

    'defaults' => [
        'app_limit' => (int) env('SCRAPING_DEFAULT_APP_LIMIT', 300),
        'concurrent_jobs' => (int) env('SCRAPING_CONCURRENT_JOBS', 5),
        'jitter_min_ms' => (int) env('SCRAPING_JITTER_MIN_MS', 200),
        'jitter_max_ms' => (int) env('SCRAPING_JITTER_MAX_MS', 800),
    ],

    'user_agents' => [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.1 Safari/605.1.15',
    ],

    'rate_limits' => [
        'shopify_per_proxy_per_second' => 2,
        'storeleads_chunk_size' => 1000,
    ],

    'cooldown' => [
        // Seconds a proxy stays out of the pool after a 429/503/Cloudflare detection.
        'proxy_after_block_seconds' => 3600,
    ],
];
