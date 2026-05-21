<?php

return [

    // Active LLM provider — switches the binding in AppServiceProvider.
    'provider' => env('AI_PROVIDER', 'ollama'),

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
        'version' => env('ANTHROPIC_API_VERSION', '2023-06-01'),
        'beta' => env('ANTHROPIC_API_BETA', 'message-batches-2024-09-24'),
        'model' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5-20251001'),
        'request_timeout' => (int) env('ANTHROPIC_TIMEOUT', 30),
    ],

    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://ollama:11434'),
        'model' => env('OLLAMA_MODEL', 'qwen2.5:7b-instruct'),
        'temperature' => (float) env('OLLAMA_TEMPERATURE', 0.2),
        'request_timeout' => (int) env('OLLAMA_TIMEOUT', 180),
        'result_ttl_days' => (int) env('OLLAMA_RESULT_TTL_DAYS', 7),
    ],

    'batch' => [
        // Reviews packed into one submission. With Ollama (sync inference)
        // keep this small: each batch blocks for ~N * inference_time seconds.
        // Bump to 100+ when using Anthropic (real provider-side batching).
        'size' => (int) env('AI_BATCH_SIZE', 20),

        // Max output tokens per review extraction. Pain-point JSON should
        // fit in ~200 tokens; budget a small buffer.
        'max_output_tokens' => (int) env('AI_BATCH_MAX_TOKENS', 400),

        // Prompt version stamped on every AiBatch row — bump when the
        // extraction schema changes so historical results stay traceable.
        'prompt_version' => env('AI_PROMPT_VERSION', 'v1-painpoints-2026-05'),
    ],

    'schedule' => [
        'compile_cron' => env('AI_COMPILE_CRON', '0 * * * *'),       // hourly
        'poll_cron' => env('AI_POLL_CRON', '*/15 * * * *'),          // every 15 min
    ],
];
