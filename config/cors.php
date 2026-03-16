<?php

$allowedOrigins = array_values(array_filter(array_map(
    static fn (string $origin): string => trim($origin),
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', env('APP_URL', 'http://localhost')))
), static fn (string $origin): bool => $origin !== ''));

$allowedMethods = array_values(array_filter(array_map(
    static fn (string $method): string => strtoupper(trim($method)),
    explode(',', (string) env('CORS_ALLOWED_METHODS', 'GET,POST,OPTIONS'))
), static fn (string $method): bool => $method !== ''));

$allowedHeaders = array_values(array_filter(array_map(
    static fn (string $header): string => trim($header),
    explode(',', (string) env('CORS_ALLOWED_HEADERS', 'Content-Type,Authorization,X-Requested-With'))
), static fn (string $header): bool => $header !== ''));

return [
    'paths' => ['api/*'],
    'allowed_methods' => $allowedMethods,
    'allowed_origins' => $allowedOrigins,
    'allowed_origins_patterns' => [],
    'allowed_headers' => $allowedHeaders,
    'exposed_headers' => [],
    'max_age' => 600,
    'supports_credentials' => false,
];
