<?php

return [
    'require_auth' => (bool) env('QUOTATIONS_REQUIRE_AUTH', false),
    // throttle signature: "maxAttempts,decayMinutes"
    'rate_limit' => env('QUOTATIONS_RATE_LIMIT', '60,1'),
    // cache ttl in seconds for external quote fetch
    'cache_ttl' => (int) env('QUOTATIONS_CACHE_TTL', 60),
    'auto_collect' => [
        'enabled' => (bool) env('QUOTATIONS_AUTO_COLLECT_ENABLED', false),
        'interval_minutes' => (int) env('QUOTATIONS_AUTO_COLLECT_INTERVAL_MINUTES', 15),
        'symbols' => array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', (string) env('QUOTATIONS_AUTO_COLLECT_SYMBOLS', 'BTC,ETH,MSFT,USD-BRL'))
        ))),
        'provider' => env('QUOTATIONS_AUTO_COLLECT_PROVIDER'),
        'history_path' => env(
            'QUOTATIONS_AUTO_COLLECT_HISTORY_PATH',
            storage_path('app/operations/collect-runs.jsonl')
        ),
        'history_fallback_path' => env(
            'QUOTATIONS_AUTO_COLLECT_HISTORY_FALLBACK_PATH',
            storage_path('framework/operations/collect-runs.local.jsonl')
        ),
    ],
    'quality' => [
        'outlier_guard' => [
            'enabled' => (bool) env('QUOTATIONS_OUTLIER_GUARD_ENABLED', true),
            'window_size' => (int) env('QUOTATIONS_OUTLIER_GUARD_WINDOW', 20),
            'min_reference_points' => (int) env('QUOTATIONS_OUTLIER_GUARD_MIN_POINTS', 4),
            'max_deviation_ratio' => (float) env('QUOTATIONS_OUTLIER_GUARD_MAX_DEVIATION_RATIO', 0.85),
        ],
    ],
];
