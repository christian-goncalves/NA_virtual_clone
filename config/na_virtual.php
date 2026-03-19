<?php

return [
    'homepage_cache' => [
        'key' => env('NA_VIRTUAL_HOMEPAGE_CACHE_KEY', 'na.virtual.homepage'),
        'ttl_seconds' => (int) env('NA_VIRTUAL_HOMEPAGE_CACHE_TTL_SECONDS', 120),
    ],

    'homepage_fallback' => [
        'max_stale_minutes' => (int) env('NA_VIRTUAL_HOMEPAGE_FALLBACK_MAX_STALE_MINUTES', 180),
    ],

    'privacy' => [
        'mask_meeting_id' => (bool) env('NA_VIRTUAL_MASK_MEETING_ID', false),
        'mask_meeting_password' => (bool) env('NA_VIRTUAL_MASK_MEETING_PASSWORD', false),
    ],

    'rate_limit' => [
        'web_public_per_minute' => (int) env('WEB_PUBLIC_RATE_LIMIT_PER_MINUTE', 120),
        'api_public_per_minute' => (int) env('API_PUBLIC_RATE_LIMIT_PER_MINUTE', 120),
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
    'metrics' => [
        'enabled' => (bool) env('NA_VIRTUAL_METRICS_ENABLED', true),
        'dashboard_cache_ttl_seconds' => (int) env('NA_VIRTUAL_METRICS_DASHBOARD_CACHE_TTL_SECONDS', 30),
        'admin_emails' => array_values(array_filter(array_map('trim', explode(',', (string) env('NA_VIRTUAL_METRICS_ADMIN_EMAILS', ''))))),
        'admin' => [
            'ip_allowlist' => array_values(array_filter(array_map('trim', explode(',', (string) env('NA_VIRTUAL_METRICS_ADMIN_IP_ALLOWLIST', ''))))),
            'require_https' => (bool) env('NA_VIRTUAL_METRICS_ADMIN_REQUIRE_HTTPS', true),
        ],
        'events' => [
            'user_agent_max_length' => (int) env('NA_VIRTUAL_METRICS_USER_AGENT_MAX_LENGTH', 255),
        ],
        'request_metrics' => [
            'enabled' => (bool) env('NA_VIRTUAL_REQUEST_METRICS_ENABLED', true),
        ],
        'hourly_aggregates' => [
            'hours_back' => (int) env('NA_VIRTUAL_METRICS_HOURLY_AGGREGATES_HOURS_BACK', 48),
        ],
        'alerts' => [
            'enabled' => (bool) env('NA_VIRTUAL_METRICS_ALERTS_ENABLED', true),
            'channel' => env('NA_VIRTUAL_METRICS_ALERTS_CHANNEL', 'na_virtual_alerts'),
            'webhook_url' => env('NA_VIRTUAL_METRICS_ALERTS_WEBHOOK_URL', ''),
            'sync_stale_minutes_threshold' => (int) env('NA_VIRTUAL_METRICS_ALERTS_SYNC_STALE_MINUTES_THRESHOLD', 90),
            'failed_run_recent_minutes' => (int) env('NA_VIRTUAL_METRICS_ALERTS_FAILED_RUN_RECENT_MINUTES', 45),
            'latency_window_minutes' => (int) env('NA_VIRTUAL_METRICS_ALERTS_LATENCY_WINDOW_MINUTES', 60),
            'latency_p95_threshold_ms' => (int) env('NA_VIRTUAL_METRICS_ALERTS_LATENCY_P95_THRESHOLD_MS', 2500),
            'min_request_samples' => (int) env('NA_VIRTUAL_METRICS_ALERTS_MIN_REQUEST_SAMPLES', 20),
            'dedupe_minutes' => (int) env('NA_VIRTUAL_METRICS_ALERTS_DEDUPE_MINUTES', 30),
            'cache_prefix' => env('NA_VIRTUAL_METRICS_ALERTS_CACHE_PREFIX', 'na.virtual.metrics.alerts.last_sent'),
        ],
        'retention' => [
            'enabled' => (bool) env('NA_VIRTUAL_METRICS_RETENTION_ENABLED', true),
            'page_views_days' => (int) env('NA_VIRTUAL_METRICS_RETENTION_PAGE_VIEWS_DAYS', 30),
            'request_metrics_days' => (int) env('NA_VIRTUAL_METRICS_RETENTION_REQUEST_METRICS_DAYS', 30),
            'sync_runs_days' => (int) env('NA_VIRTUAL_METRICS_RETENTION_SYNC_RUNS_DAYS', 90),
            'meeting_snapshots_days' => (int) env('NA_VIRTUAL_METRICS_RETENTION_MEETING_SNAPSHOTS_DAYS', 90),
            'hourly_aggregates_days' => (int) env('NA_VIRTUAL_METRICS_RETENTION_HOURLY_AGGREGATES_DAYS', 180),
        ],
    ],
];

