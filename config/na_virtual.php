<?php

return [
    'homepage_cache' => [
        'key' => env('NA_VIRTUAL_HOMEPAGE_CACHE_KEY', 'na.virtual.homepage'),
        'ttl_seconds' => (int) env('NA_VIRTUAL_HOMEPAGE_CACHE_TTL_SECONDS', 120),
    ],

    'homepage_fallback' => [
        'max_stale_minutes' => (int) env('NA_VIRTUAL_HOMEPAGE_FALLBACK_MAX_STALE_MINUTES', 180),
    ],

    'sync_guard' => [
        // Minimum number of meetings found in current run before allowing bulk inactivation.
        'min_found_for_inactivation' => (int) env('NA_VIRTUAL_SYNC_GUARD_MIN_FOUND_FOR_INACTIVATION', 5),

        // Minimum ratio (found / previously_active) to allow inactivation.
        'min_ratio_for_inactivation' => (float) env('NA_VIRTUAL_SYNC_GUARD_MIN_RATIO_FOR_INACTIVATION', 0.20),
    ],

    'snapshot' => [
        'context_homepage' => env('NA_VIRTUAL_SNAPSHOT_CONTEXT_HOMEPAGE', 'na.virtual.homepage'),
        'max_records' => (int) env('NA_VIRTUAL_SNAPSHOT_MAX_RECORDS', 50),
    ],

    'sync_status' => [
        'last_success_cache_key' => env('NA_VIRTUAL_SYNC_STATUS_LAST_SUCCESS_CACHE_KEY', 'na.virtual.sync.last_success_at'),
        'last_failure_cache_key' => env('NA_VIRTUAL_SYNC_STATUS_LAST_FAILURE_CACHE_KEY', 'na.virtual.sync.last_failure_at'),
    ],

    'alerts' => [
        'enabled' => (bool) env('NA_VIRTUAL_ALERTS_ENABLED', true),
        'channel' => env('NA_VIRTUAL_ALERTS_CHANNEL', 'na_virtual_alerts'),
        'consecutive_failures_threshold' => (int) env('NA_VIRTUAL_ALERTS_CONSECUTIVE_FAILURES_THRESHOLD', 3),
        'consecutive_failures_cache_key' => env('NA_VIRTUAL_ALERTS_CONSECUTIVE_FAILURES_CACHE_KEY', 'na.virtual.alerts.consecutive_failures'),
        'volume_drop_percent_threshold' => (float) env('NA_VIRTUAL_ALERTS_VOLUME_DROP_PERCENT_THRESHOLD', 60),
        'min_active_base_for_volume_alert' => (int) env('NA_VIRTUAL_ALERTS_MIN_ACTIVE_BASE_FOR_VOLUME_ALERT', 50),
        'webhook_url' => env('NA_VIRTUAL_ALERTS_WEBHOOK_URL', ''),
    ],
];
