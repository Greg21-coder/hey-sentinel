<?php

namespace App\Console\Commands;

use App\Jobs\Ai\PollBatchesJob;
use Illuminate\Console\Command;

class AiPollBatchesCommand extends Command
{
    protected $signature = 'app:ai:poll-batches';

    protected $description = 'Poll in-progress LLM batches and dispatch ingest jobs for completed ones.';

    public function handle(): int
    {
        PollBatchesJob::dispatch();

        $this->info('PollBatchesJob dispatched.');

        return self::SUCCESS;
    }
}
