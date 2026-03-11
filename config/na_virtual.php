<?php

return [
    'homepage_cache' => [
        'key' => env('NA_VIRTUAL_HOMEPAGE_CACHE_KEY', 'na.virtual.homepage'),
        'ttl_seconds' => (int) env('NA_VIRTUAL_HOMEPAGE_CACHE_TTL_SECONDS', 120),
    ],

    'sync_guard' => [
        // Minimum number of meetings found in current run before allowing bulk inactivation.
        'min_found_for_inactivation' => (int) env('NA_VIRTUAL_SYNC_GUARD_MIN_FOUND_FOR_INACTIVATION', 5),

        // Minimum ratio (found / previously_active) to allow inactivation.
        'min_ratio_for_inactivation' => (float) env('NA_VIRTUAL_SYNC_GUARD_MIN_RATIO_FOR_INACTIVATION', 0.20),
    ],
];
