<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pony AI - Retrieval and Ranking Knobs
    |--------------------------------------------------------------------------
    |
    | These values are read by the customer and seller strategies. Anything
    | the strategies need to tune at runtime lives here so a redeploy is the
    | only thing required to flip a number.
    |
    */

    'retrieval' => [
        // Max SQL candidates fed into the embedding reranker.
        'candidate_limit' => (int) env('PONY_CANDIDATE_LIMIT', 50),

        // Max products surfaced to Gemini and (after grounding) to the user.
        'rerank_top_k' => (int) env('PONY_RERANK_TOP_K', 8),

        // Weight of the image-vs-image cosine in the image-search blended score.
        // 0.0 = text only, 1.0 = image only.
        'image_rank_alpha' => (float) env('PONY_IMAGE_RANK_ALPHA', 0.6),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pony AI - Rate Limits
    |--------------------------------------------------------------------------
    |
    | The PonyAIRateLimiter middleware reads these values to throttle chat and
    | image-search endpoints. Limits are measured per authenticated user and
    | fall back to the requester IP when no user is attached.
    |
    */

    'rate_limits' => [
        // POST /v1/pony/customer/chat and POST /v1/pony/stores/{store}/chat
        'text' => [
            'max_attempts' => (int) env('PONY_RATE_TEXT_MAX', 30),
            'decay_seconds' => (int) env('PONY_RATE_TEXT_DECAY', 60),
        ],
        // POST /v1/pony/customer/image-search
        'image' => [
            'max_attempts' => (int) env('PONY_RATE_IMAGE_MAX', 10),
            'decay_seconds' => (int) env('PONY_RATE_IMAGE_DECAY', 60),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pony AI - Logging
    |--------------------------------------------------------------------------
    |
    | The strategies emit a structured log line per turn on this channel.
    | The line carries persona, candidate count, returned count, dropped count,
    | latency and token usage. It never carries the API key or full prompt.
    |
    */

    'logging' => [
        'channel' => env('PONY_LOG_CHANNEL', 'pony'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pony AI - Prompt Sanitization
    |--------------------------------------------------------------------------
    |
    | When true the user's prompt is run through PromptSanitizer before being
    | forwarded to Gemini. The sanitizer strips control bytes and a small list
    | of known prompt-injection markers; the original user-typed text is still
    | persisted to pony_messages.content for transparency.
    |
    */

    'sanitize_prompts' => filter_var(env('PONY_SANITIZE_PROMPTS', true), FILTER_VALIDATE_BOOL),

    /*
    |--------------------------------------------------------------------------
    | Pony AI - Query Image Storage
    |--------------------------------------------------------------------------
    |
    | Uploaded image queries are kept on the LOCAL disk under pony/queries/*.
    | The resource layer surfaces them via a Laravel signed URL that expires
    | after `image_url_ttl_minutes`, and `pony:purge-image-queries` deletes
    | files older than `image_retention_days` (default 14 days).
    |
    */

    'image_url_ttl_minutes' => (int) env('PONY_IMAGE_URL_TTL_MINUTES', 30),

    'image_retention_days' => (int) env('PONY_IMAGE_RETENTION_DAYS', 14),

];
