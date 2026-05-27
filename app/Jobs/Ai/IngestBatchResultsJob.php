<?php

namespace App\Jobs\Ai;

use App\Contracts\BatchLlmClient;
use App\Enums\AiStatus;
use App\Models\AiBatch;
use App\Models\AiPainPoint;
use App\Models\StoreReview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IngestBatchResultsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $aiBatchId) {}

    public function handle(BatchLlmClient $client): void
    {
        /** @var AiBatch $batch */
        $batch = AiBatch::findOrFail($this->aiBatchId);

        try {
            $records = $client->fetchResults($batch->batch_id);
        } catch (\Throwable $e) {
            Log::error('IngestBatchResultsJob: fetchResults failed', [
                'batch_id' => $batch->batch_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        $slugToId = AiPainPoint::pluck('id', 'slug')->all();

        $processed = 0;
        $errored = 0;

        foreach ($records as $record) {
            $reviewId = $this->reviewIdFromCustomId($record['custom_id'] ?? null);
            if ($reviewId === null) {
                continue;
            }

            $extraction = $this->extractJson($record);
            if ($extraction === null) {
                StoreReview::where('id', $reviewId)
                    ->update(['ai_status' => AiStatus::Error->value]);
                $errored++;

                continue;
            }

            $this->persistExtraction($reviewId, $extraction, $slugToId);
            $processed++;
        }

        $batch->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        Log::info('IngestBatchResultsJob success', [
            'batch_id' => $batch->batch_id,
            'processed' => $processed,
            'errored' => $errored,
            'records' => count($records),
        ]);
    }

    protected function reviewIdFromCustomId(?string $customId): ?int
    {
        if ($customId === null || ! preg_match('/^review-(\d+)$/', $customId, $m)) {
            return null;
        }

        return (int) $m[1];
    }

    /**
     * @return array{sentiment: string, pain_points: list<array{slug: string, severity: string, confidence: float}>}|null
     */
    protected function extractJson(array $record): ?array
    {
        $type = $record['result']['type'] ?? null;
        if ($type !== 'succeeded') {
            return null;
        }

        $text = $record['result']['message']['content'][0]['text'] ?? null;
        if (! is_string($text)) {
            return null;
        }

        // Strip markdown fences if the model wrapped JSON.
        $text = trim(preg_replace('/```(?:json)?|```/', '', $text));

        $decoded = json_decode($text, true);
        if (! is_array($decoded)) {
            return null;
        }

        // The schema requires BOTH keys present and well-typed. A response that
        // is parseable JSON but missing them (e.g. {} from a model that gave up)
        // must be rejected — otherwise the review silently lands as
        // sentiment=neutral / pain_points=[], indistinguishable from a real
        // "no pain points" answer.
        $validSentiments = ['positive', 'negative', 'neutral', 'mixed'];
        $sentiment = $decoded['sentiment'] ?? null;
        $painPoints = $decoded['pain_points'] ?? null;

        if (! is_string($sentiment) || ! in_array($sentiment, $validSentiments, true)) {
            Log::warning('IngestBatchResultsJob.extractJson rejected: invalid sentiment', [
                'sentiment' => $sentiment,
                'raw' => mb_substr($text, 0, 500),
            ]);

            return null;
        }

        if (! is_array($painPoints)) {
            Log::warning('IngestBatchResultsJob.extractJson rejected: pain_points missing or not array', [
                'pain_points' => $painPoints,
                'raw' => mb_substr($text, 0, 500),
            ]);

            return null;
        }

        $normalized = [];
        foreach ($painPoints as $pp) {
            if (! is_array($pp) || ! isset($pp['slug']) || ! is_string($pp['slug'])) {
                continue;
            }
            $normalized[] = [
                'slug' => $pp['slug'],
                'severity' => in_array($pp['severity'] ?? null, ['low', 'medium', 'high'], true)
                    ? $pp['severity']
                    : 'medium',
                'confidence' => is_numeric($pp['confidence'] ?? null)
                    ? max(0.0, min(1.0, (float) $pp['confidence']))
                    : 1.0,
            ];
        }

        return ['sentiment' => $sentiment, 'pain_points' => $normalized];
    }

    /**
     * @param  array{sentiment: string, pain_points: list<array{slug: string, severity: string, confidence: float}>}  $extraction
     * @param  array<string, int>  $slugToId
     */
    protected function persistExtraction(int $reviewId, array $extraction, array $slugToId): void
    {
        DB::transaction(function () use ($reviewId, $extraction, $slugToId) {
            StoreReview::where('id', $reviewId)->update([
                'ai_sentiment' => $extraction['sentiment'],
                'ai_status' => AiStatus::Processed->value,
                'ai_processed_at' => now(),
            ]);

            $now = now()->format('Y-m-d H:i:s');
            $rows = [];
            foreach ($extraction['pain_points'] as $pp) {
                $painPointId = $slugToId[$pp['slug']] ?? null;
                if ($painPointId === null) {
                    continue;
                }
                $rows[] = [
                    'review_id' => $reviewId,
                    'pain_point_id' => $painPointId,
                    'severity' => $pp['severity'],
                    'confidence' => $pp['confidence'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (! empty($rows)) {
                DB::table('review_pain_point')->insertOrIgnore($rows);
            }
        });
    }
}
