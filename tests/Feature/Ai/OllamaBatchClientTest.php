<?php

use App\Services\Ai\OllamaBatchClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

it('processes requests sync, caches results, polls completed, returns Anthropic-shaped records', function () {
    Http::fake([
        '*/api/chat' => Http::response([
            'message' => ['content' => '{"sentiment":"negative","pain_points":[]}'],
        ], 200),
    ]);

    $client = new OllamaBatchClient();

    $requests = [
        ['custom_id' => 'review-1', 'params' => [
            'model' => 'qwen2.5:7b-instruct',
            'system' => 'extract',
            'messages' => [['role' => 'user', 'content' => 'rating 1 / bad app']],
        ]],
        ['custom_id' => 'review-2', 'params' => [
            'model' => 'qwen2.5:7b-instruct',
            'system' => 'extract',
            'messages' => [['role' => 'user', 'content' => 'rating 5 / great app']],
        ]],
    ];

    $batchId = $client->submitBatch($requests, 'v1-test');

    expect($batchId)->toStartWith('ollama-');
    expect($client->pollBatch($batchId))->toBe('completed');

    $results = $client->fetchResults($batchId);
    expect($results)->toHaveCount(2);
    expect($results[0]['custom_id'])->toBe('review-1');
    expect($results[0]['result']['type'])->toBe('succeeded');
    expect($results[0]['result']['message']['content'][0]['text'])->toContain('"sentiment"');

    Http::assertSentCount(2);
});

it('returns errored result when Ollama HTTP fails', function () {
    Http::fake(['*/api/chat' => Http::response('boom', 500)]);

    $client = new OllamaBatchClient();
    $batchId = $client->submitBatch([
        ['custom_id' => 'review-99', 'params' => ['system' => '', 'messages' => [['role' => 'user', 'content' => 'x']]]],
    ], 'v1');

    $results = $client->fetchResults($batchId);
    expect($results[0]['result']['type'])->toBe('errored');
    expect($results[0]['result']['error']['message'])->toContain('500');
});

it('marks poll as failed when results were never cached', function () {
    Cache::flush();
    $client = new OllamaBatchClient();
    expect($client->pollBatch('ollama-nonexistent'))->toBe('failed');
});
