<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// HeySentinel — scraping pipeline (Tier 0 conservative cadence).
Schedule::command('app:discover:apps')->twiceDaily(2, 14)->withoutOverlapping();
Schedule::command('app:webshare:refresh')->dailyAt('03:00')->withoutOverlapping();
Schedule::command('app:scrape:apps --limit=300')->dailyAt('04:00')->withoutOverlapping();
Schedule::command('app:scrape:reviews --pages=3')->everySixHours()->withoutOverlapping();
Schedule::command('app:storeleads:sync --scope=reviewers')->dailyAt('05:00')->withoutOverlapping();

// HeySentinel — AI pain-point extraction (Anthropic Batch API).
Schedule::command('app:ai:compile-batch')->cron(config('ai.schedule.compile_cron'))->withoutOverlapping();
Schedule::command('app:ai:poll-batches')->cron(config('ai.schedule.poll_cron'))->withoutOverlapping();
