<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// HeySentinel — integrated pipeline (discovery → scrape → reviews → AI).
// Discovery finds new apps and auto-dispatches scraping for them.
// ScrapeAppPageJob auto-dispatches review scraping on success.
// ScrapeReviewPageJob auto-dispatches CompileBatchJob when new reviews arrive.
Schedule::command('app:discover:apps')->twiceDaily(2, 14)->withoutOverlapping();

// Safety net: re-enqueue any pending/error apps that fell through.
Schedule::command('app:scrape:apps --limit=1000')->dailyAt('04:00')->withoutOverlapping();

// Storeleads enrichment for store data.
Schedule::command('app:storeleads:sync --scope=reviewers')->dailyAt('05:00')->withoutOverlapping();

// AI batch polling for async providers (Anthropic). Sync providers (Ollama)
// are handled inline by CompileBatchJob → IngestBatchResultsJob.
Schedule::command('app:ai:compile-batch')->cron(config('ai.schedule.compile_cron'))->withoutOverlapping();
Schedule::command('app:ai:poll-batches')->cron(config('ai.schedule.poll_cron'))->withoutOverlapping();
