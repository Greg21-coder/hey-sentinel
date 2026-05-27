<?php

namespace App\Jobs\Ai;

use App\Contracts\BatchLlmClient;
use App\Enums\AiStatus;
use App\Models\AiBatch;
use App\Models\StoreReview;
use App\Support\Ai\PainPointExtractionPrompt;
use App\Jobs\Ai\IngestBatchResultsJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompileBatchJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    // Ollama processes the whole batch synchronously inside submitBatch(),
    // so this job can block for several minutes on CPU-only inference.
    public int $timeout = 1800;

    // The cron and the Filament "Reprocess AI" button can fire simultaneously.
    // Without a uniqueness guard, both jobs SELECT the same pending reviews
    // before either commits the status flip, submitting overlapping batches
    // (and double-billing on Anthropic). uniqueFor matches $timeout so the lock
    // outlives the worst-case sync Ollama run.
    public int $uniqueFor = 1800;

    public function uniqueId(): string
    {
        return 'compile-batch';
    }

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

        try {
            $batchId = $client->submitBatch($requests, $promptVersion);
        } catch (\Throwable $e) {
            Log::error('CompileBatchJob: submitBatch failed', [
                'error' => $e->getMessage(),
                'review_count' => count($requests),
            ]);

            throw $e;
        }

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
        try {
            if ($client->pollBatch($batchId) === 'completed') {
                IngestBatchResultsJob::dispatch($aiBatch->id);
            }
        } catch (\Throwable $e) {
            Log::warning('CompileBatchJob: pollBatch failed, cron will retry', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
