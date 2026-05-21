<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('shopify-scrape', function () {
            $perSecond = (int) config('scraping.rate_limits.shopify_per_proxy_per_second', 2);

            return Limit::perSecond($perSecond);
        });
    }
}
