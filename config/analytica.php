<?php

return [
    'storage_disk' => env('ANALYTICA_STORAGE_DISK', 's3'),

    // Derive a stable 32-byte key from APP_KEY if not provided.
    'encryption_key' => env('ANALYTICA_FILE_ENCRYPTION_KEY'),

    'case_expiration_months' => (int) env('ANALYTICA_CASE_EXPIRATION_MONTHS', 24),

    'encryption' => [
        'algorithm' => 'aes-256-gcm',
        'nonce_bytes' => 12,
        'tag_bytes' => 16,
    ],

    'ocr_url' => env('ANALYTICA_OCR_URL'),

    'import' => [
        'mode' => env('ANALYTICA_IMPORT_MODE', 'expert'),
        'strict' => (bool) env('ANALYTICA_IMPORT_STRICT', true),
        'min_confidence' => (int) env('ANALYTICA_IMPORT_MIN_CONFIDENCE', 45),
        'high_value_threshold' => (float) env('ANALYTICA_IMPORT_HIGH_VALUE_THRESHOLD', 20000),
    ],

    'ai' => [
        'enabled' => (bool) env('ANALYTICA_AI_ENABLED', false),
        'openai_base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'openai_model' => env('ANALYTICA_AI_MODEL', 'gpt-4.1-mini'),
        'max_transactions' => (int) env('ANALYTICA_AI_MAX_TRANSACTIONS', 300),
        'timeout_seconds' => (int) env('ANALYTICA_AI_TIMEOUT', 45),
    ],
];
