<?php

namespace App\Services\Ai;

use App\Contracts\BatchLlmClient;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AnthropicBatchClient implements BatchLlmClient
{
    public function submitBatch(array $requests, string $promptVersion): string
    {
        $response = $this->http()->post('/v1/messages/batches', [
            'requests' => $requests,
        ]);

        if ($response->failed()) {
            throw new RuntimeException(
                "Anthropic submitBatch failed: HTTP {$response->status()} {$response->body()}"
            );
        }

        $batchId = $response->json('id');
        if (! is_string($batchId) || $batchId === '') {
            throw new RuntimeException('Anthropic submitBatch returned no id.');
        }

        return $batchId;
    }

    public function pollBatch(string $batchId): string
    {
        $response = $this->http()->get("/v1/messages/batches/{$batchId}");

        if ($response->failed()) {
            throw new RuntimeException(
                "Anthropic pollBatch failed: HTTP {$response->status()} {$response->body()}"
            );
        }

        $processingStatus = $response->json('processing_status');

        return match ($processingStatus) {
            'in_progress' => 'in_progress',
            'canceling' => 'in_progress',
            'ended' => 'completed',
            default => 'submitted',
        };
    }

    public function fetchResults(string $batchId): array
    {
        $url = $this->http()->get("/v1/messages/batches/{$batchId}")->json('results_url');
        if (! is_string($url) || $url === '') {
            throw new RuntimeException("Anthropic batch {$batchId} has no results_url.");
        }

        $response = $this->http()->withOptions(['stream' => false])->get($url);
        if ($response->failed()) {
            throw new RuntimeException(
                "Anthropic fetchResults failed: HTTP {$response->status()} {$response->body()}"
            );
        }

        $records = [];
        foreach (preg_split("/\r\n|\r|\n/", trim($response->body())) as $line) {
            if ($line === '') {
                continue;
            }
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $records[] = $decoded;
            }
        }

        return $records;
    }

    protected function http(): PendingRequest
    {
        $apiKey = (string) config('ai.anthropic.api_key');
        if ($apiKey === '') {
            throw new RuntimeException('ANTHROPIC_API_KEY is not set.');
        }

        return Http::baseUrl((string) config('ai.anthropic.base_url'))
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => (string) config('ai.anthropic.version'),
                'anthropic-beta' => (string) config('ai.anthropic.beta'),
                'content-type' => 'application/json',
            ])
            ->timeout((int) config('ai.anthropic.request_timeout'))
            ->acceptJson();
    }
}
