<?php

namespace App\Console\Commands;

use App\Jobs\Ai\CompileBatchJob;
use Illuminate\Console\Command;

class AiCompileBatchCommand extends Command
{
    protected $signature = 'app:ai:compile-batch';

    protected $description = 'Compile a batch of pending reviews and submit to the LLM provider.';

    public function handle(): int
    {
        CompileBatchJob::dispatch();

        $this->info('CompileBatchJob dispatched.');

        return self::SUCCESS;
    }
}
