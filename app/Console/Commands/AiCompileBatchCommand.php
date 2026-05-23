<?php

namespace App\Console\Commands;

use App\Jobs\Ai\CompileBatchJob;
use Illuminate\Console\Command;

class AiCompileBatchCommand extends Command
{
    protected $signature = 'app:ai:compile-batch {--count=1 : Number of batches to dispatch}';

    protected $description = 'Compile one or more batches of pending reviews and submit to the LLM provider.';

    public function handle(): int
    {
        $count = max(1, (int) $this->option('count'));

        for ($i = 0; $i < $count; $i++) {
            CompileBatchJob::dispatch();
        }

        $this->info("Dispatched {$count} CompileBatchJob(s).");

        return self::SUCCESS;
    }
}
