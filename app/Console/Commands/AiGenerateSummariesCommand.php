<?php

namespace App\Console\Commands;

use App\Enums\ScrapingStatus;
use App\Models\ShopifyApp;
use App\Services\Ai\AppSummaryService;
use Illuminate\Console\Command;
use Throwable;

class AiGenerateSummariesCommand extends Command
{
    protected $signature = 'app:ai:generate-summaries
        {--app= : Specific shopify_apps.id; if omitted, processes all scraped apps with processed reviews}
        {--stale-days=7 : Skip apps whose ai_summary_at is younger than this}
        {--force : Regenerate even if summary is fresh}';

    protected $description = 'Generate per-app TL;DR summaries from processed reviews via Ollama.';

    public function handle(AppSummaryService $service): int
    {
        $query = ShopifyApp::query()->where('scraping_status', ScrapingStatus::Scraped->value);

        if ($id = $this->option('app')) {
            $query->where('id', $id);
        }

        if (! $this->option('force')) {
            $cutoff = now()->subDays((int) $this->option('stale-days'));
            $query->where(function ($q) use ($cutoff) {
                $q->whereNull('ai_summary_at')->orWhere('ai_summary_at', '<', $cutoff);
            });
        }

        $apps = $query->get();

        if ($apps->isEmpty()) {
            $this->info('No apps need summary generation.');
            return self::SUCCESS;
        }

        $this->info("Generating summaries for {$apps->count()} app(s)...");

        $generated = 0;
        $skipped = 0;
        $errored = 0;

        foreach ($apps as $app) {
            try {
                $summary = $service->generateFor($app);
                if ($summary === null) {
                    $this->warn("  - {$app->shopify_app_handle}: skipped (insufficient processed reviews)");
                    $skipped++;
                } else {
                    $this->info("  ✓ {$app->shopify_app_handle}: ".mb_substr($summary, 0, 80).'...');
                    $generated++;
                }
            } catch (Throwable $e) {
                $this->error("  ✗ {$app->shopify_app_handle}: {$e->getMessage()}");
                $errored++;
            }
        }

        $this->newLine();
        $this->info("Done. Generated: {$generated}, skipped: {$skipped}, errored: {$errored}.");

        return $errored > 0 ? self::FAILURE : self::SUCCESS;
    }
}
