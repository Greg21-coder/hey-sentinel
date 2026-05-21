<?php

namespace App\Services\Ai;

use App\Contracts\BatchLlmClient;

/**
 * In-memory fake for tests + local smoke runs without an API key.
 *
 * Records submissions and returns pre-canned responses keyed by batch id.
 */
class FakeBatchLlmClient implements BatchLlmClient
{
    /** @var array<int, array{batchId: string, promptVersion: string, requests: list<array<string, mixed>>}> */
    public array $submissions = [];

    /** @var array<string, string> */
    public array $pollStatuses = [];

    /** @var array<string, list<array<string, mixed>>> */
    public array $resultsByBatchId = [];

    public int $nextId = 1;

    public function submitBatch(array $requests, string $promptVersion): string
    {
        $batchId = 'fake-batch-'.str_pad((string) $this->nextId++, 4, '0', STR_PAD_LEFT);
        $this->submissions[] = [
            'batchId' => $batchId,
            'promptVersion' => $promptVersion,
            'requests' => $requests,
        ];
        $this->pollStatuses[$batchId] ??= 'completed';

        return $batchId;
    }

    public function pollBatch(string $batchId): string
    {
        return $this->pollStatuses[$batchId] ?? 'submitted';
    }

    public function fetchResults(string $batchId): array
    {
        return $this->resultsByBatchId[$batchId] ?? [];
    }

    /**
     * Helper to pre-stage results for a given submission position (0-indexed).
     *
     * @param  list<array<string, mixed>>  $results
     */
    public function stageResultsForSubmission(int $index, array $results): void
    {
        $batchId = $this->submissions[$index]['batchId'] ?? null;
        if ($batchId !== null) {
            $this->resultsByBatchId[$batchId] = $results;
        }
    }
}
