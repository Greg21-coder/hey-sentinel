<?php

namespace App\Contracts;

/**
 * Provider-agnostic interface for the AI batching pipeline.
 *
 * Implementations live in app/Services/Ai/. Phase 4 wires the
 * default binding (AnthropicBatchClient) in a ServiceProvider.
 */
interface BatchLlmClient
{
    /**
     * Submit a JSONL batch of requests; return the provider batch id.
     *
     * @param  list<array<string, mixed>>  $requests
     */
    public function submitBatch(array $requests, string $promptVersion): string;

    /**
     * Poll for status. Returns one of:
     *   'submitted' | 'in_progress' | 'completed' | 'failed'
     */
    public function pollBatch(string $batchId): string;

    /**
     * Fetch parsed results for a completed batch.
     *
     * @return list<array<string, mixed>> one record per input request
     */
    public function fetchResults(string $batchId): array;
}
