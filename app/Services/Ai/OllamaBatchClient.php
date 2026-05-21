<?php

namespace App\Services\Ai;

use App\Contracts\BatchLlmClient;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Ollama adapter that fakes the Anthropic Batch shape on top of synchronous
 * per-request /api/chat calls. submitBatch() processes the whole batch inline
 * (blocking) and caches results in Redis for fetchResults() to read later.
 *
 * Trade-off: there is no real provider-side batching, so the queue worker
 * blocks for ~N * inference_time seconds. CompileBatchJob.timeout is set
 * accordingly and AI_BATCH_SIZE defaults to 20.
 */
class OllamaBatchClient implements BatchLlmClient
{
    public const CACHE_PREFIX = 'ollama-batch:';

    public function submitBatch(array $requests, string $promptVersion): string
    {
        $batchId = 'ollama-'.Str::ulid()->toBase32();
        $results = [];

        foreach ($requests as $request) {
            $results[] = $this->processOne($request);
        }

        Cache::put(
            self::CACHE_PREFIX.$batchId,
            $results,
            now()->addDays((int) config('ai.ollama.result_ttl_days', 7))
        );

        Log::info('OllamaBatchClient.submitBatch processed', [
            'batch_id' => $batchId,
            'count' => count($results),
            'prompt_version' => $promptVersion,
        ]);

        return $batchId;
    }

    public function pollBatch(string $batchId): string
    {
        // Inference happened synchronously inside submitBatch().
        return Cache::has(self::CACHE_PREFIX.$batchId) ? 'completed' : 'failed';
    }

    public function fetchResults(string $batchId): array
    {
        $results = Cache::get(self::CACHE_PREFIX.$batchId);

        if (! is_array($results)) {
            throw new RuntimeException("Ollama batch {$batchId} has no cached results.");
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $request
     * @return array<string, mixed>
     */
    protected function processOne(array $request): array
    {
        $customId = (string) ($request['custom_id'] ?? '');
        $params = $request['params'] ?? [];
        $system = (string) ($params['system'] ?? '');
        $userMessage = '';
        foreach ($params['messages'] ?? [] as $msg) {
            if (($msg['role'] ?? null) === 'user') {
                $userMessage = (string) ($msg['content'] ?? '');
                break;
            }
        }

        try {
            $response = $this->http()->post('/api/chat', [
                'model' => (string) config('ai.ollama.model'),
                'stream' => false,
                'format' => 'json',
                'options' => [
                    'temperature' => (float) config('ai.ollama.temperature', 0.2),
                    'num_predict' => (int) config('ai.batch.max_output_tokens'),
                ],
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $userMessage],
                ],
            ]);

            if ($response->failed()) {
                return $this->errorResult($customId, "HTTP {$response->status()} {$response->body()}");
            }

            $text = $response->json('message.content');
            if (! is_string($text) || $text === '') {
                return $this->errorResult($customId, 'empty response');
            }

            return [
                'custom_id' => $customId,
                'result' => [
                    'type' => 'succeeded',
                    'message' => [
                        'content' => [[
                            'type' => 'text',
                            'text' => $text,
                        ]],
                    ],
                ],
            ];
        } catch (\Throwable $e) {
            return $this->errorResult($customId, $e->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function errorResult(string $customId, string $message): array
    {
        return [
            'custom_id' => $customId,
            'result' => [
                'type' => 'errored',
                'error' => ['message' => $message],
            ],
        ];
    }

    protected function http(): PendingRequest
    {
        return Http::baseUrl((string) config('ai.ollama.base_url'))
            ->timeout((int) config('ai.ollama.request_timeout', 120))
            ->acceptJson();
    }
}
