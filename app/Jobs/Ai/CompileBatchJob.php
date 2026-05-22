<?php

namespace App\Jobs\Ai;

use App\Contracts\BatchLlmClient;
use App\Enums\AiStatus;
use App\Models\AiBatch;
use App\Models\StoreReview;
use App\Support\Ai\PainPointExtractionPrompt;
use App\Jobs\Ai\IngestBatchResultsJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompileBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    // Ollama processes the whole batch synchronously inside submitBatch(),
    // so this job can block for several minutes on CPU-only inference.
    public int $timeout = 1800;

    public function handle(BatchLlmClient $client): void
    {
        $size = (int) config('ai.batch.size');
        $reviews = StoreReview::query()
            ->pendingAi()
            ->orderBy('published_at', 'desc')
            ->limit($size)
            ->get();

        if ($reviews->isEmpty()) {
            Log::info('CompileBatchJob skipped: no pending reviews.');

            return;
        }

        $provider = (string) config('ai.provider');
        $model = (string) config("ai.{$provider}.model");
        $maxTokens = (int) config('ai.batch.max_output_tokens');
        $promptVersion = (string) config('ai.batch.prompt_version');
        $system = PainPointExtractionPrompt::system();

        $requests = $reviews->map(fn (StoreReview $r) => [
            'custom_id' => "review-{$r->id}",
            'params' => [
                'model' => $model,
                'max_tokens' => $maxTokens,
                'system' => $system,
                'messages' => [[
                    'role' => 'user',
                    'content' => PainPointExtractionPrompt::userMessage($r->review_text, $r->rating),
                ]],
            ],
        ])->all();

        $batchId = $client->submitBatch($requests, $promptVersion);

        $aiBatch = DB::transaction(function () use ($reviews, $batchId, $provider, $promptVersion, $requests) {
            $batch = AiBatch::create([
                'provider' => $provider,
                'batch_id' => $batchId,
                'status' => 'submitted',
                'request_count' => count($requests),
                'prompt_version' => $promptVersion,
                'submitted_at' => now(),
            ]);

            StoreReview::whereIn('id', $reviews->pluck('id'))
                ->update(['ai_status' => AiStatus::Batched->value]);

            return $batch;
        });

        Log::info('CompileBatchJob success', [
            'batch_id' => $batchId,
            'count' => count($requests),
        ]);

        // Sync providers (Ollama) report completed immediately. Skip the
        // PollBatches cron and dispatch ingest now so iteration is fast.
        // Async providers (Anthropic) report 'submitted' / 'in_progress';
        // the cron handles those.
        if ($client->pollBatch($batchId) === 'completed') {
            IngestBatchResultsJob::dispatch($aiBatch->id);
        }
    }
}
