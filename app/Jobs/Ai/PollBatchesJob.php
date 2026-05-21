<?php

namespace App\Jobs\Ai;

use App\Contracts\BatchLlmClient;
use App\Models\AiBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PollBatchesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function handle(BatchLlmClient $client): void
    {
        $batches = AiBatch::query()->open()->get();

        $advanced = 0;
        $dispatched = 0;

        foreach ($batches as $batch) {
            $status = $client->pollBatch($batch->batch_id);

            if ($status !== $batch->status) {
                $batch->update(['status' => $status]);
                $advanced++;
            }

            if ($status === 'completed') {
                IngestBatchResultsJob::dispatch($batch->id);
                $dispatched++;
            }
        }

        Log::info('PollBatchesJob success', [
            'checked' => $batches->count(),
            'status_changed' => $advanced,
            'ingest_dispatched' => $dispatched,
        ]);
    }
}
