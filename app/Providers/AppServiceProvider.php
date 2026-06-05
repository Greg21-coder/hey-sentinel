<?php

namespace App\Providers;

use App\Contracts\BatchLlmClient;
use App\Events\AppFollowed;
use App\Listeners\DispatchFeatureExtractionOnFollow;
use App\Services\Ai\AnthropicBatchClient;
use App\Services\Ai\OllamaBatchClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BatchLlmClient::class, function ($app) {
            return match ((string) config('ai.provider')) {
                'anthropic' => $app->make(AnthropicBatchClient::class),
                default => $app->make(OllamaBatchClient::class),
            };
        });
    }

    public function boot(): void
    {
        Event::listen(AppFollowed::class, DispatchFeatureExtractionOnFollow::class);

        RateLimiter::for('shopify-scrape', function () {
            $perSecond = (int) config('scraping.rate_limits.shopify_per_proxy_per_second', 2);

            return Limit::perSecond($perSecond);
        });
    }
}
