<?php

namespace Tests\Feature;

use App\Models\MetricRequestMetric;
use App\Models\MetricSyncRun;
use App\Services\NaVirtualMeetingMetricsOperationalAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetricsOperationalAlertsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_sync_stale_alert(): void
    {
        config()->set('na_virtual.metrics.alerts.enabled', true);
        config()->set('na_virtual.metrics.alerts.webhook_url', 'https://alerts.local/metrics');
        config()->set('na_virtual.metrics.alerts.sync_stale_minutes_threshold', 60);
        config()->set('na_virtual.metrics.alerts.failed_run_recent_minutes', 1);
        config()->set('na_virtual.metrics.alerts.min_request_samples', 999);
        config()->set('na_virtual.metrics.alerts.cache_prefix', 'na.virtual.metrics.alerts.test1');

        MetricSyncRun::query()->create([
            'started_at' => now()->subMinutes(180),
            'finished_at' => now()->subMinutes(179),
            'duration_ms' => 1200,
            'status' => 'success',
            'source_url' => 'https://www.na.org.br/virtual/',
        ]);

        Http::fake([
            'https://alerts.local/metrics' => Http::response([], 200),
        ]);

        app(NaVirtualMeetingMetricsOperationalAlertService::class)->evaluateHealth(now());

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://alerts.local/metrics'
                && $request['type'] === 'sync_stale';
        });
    }

    public function test_dispatches_high_latency_alert(): void
    {
        config()->set('na_virtual.metrics.alerts.enabled', true);
        config()->set('na_virtual.metrics.alerts.webhook_url', 'https://alerts.local/metrics');
        config()->set('na_virtual.metrics.alerts.sync_stale_minutes_threshold', 9999);
        config()->set('na_virtual.metrics.alerts.failed_run_recent_minutes', 1);
        config()->set('na_virtual.metrics.alerts.latency_window_minutes', 60);
        config()->set('na_virtual.metrics.alerts.latency_p95_threshold_ms', 500);
        config()->set('na_virtual.metrics.alerts.min_request_samples', 3);
        config()->set('na_virtual.metrics.alerts.cache_prefix', 'na.virtual.metrics.alerts.test2');

        MetricSyncRun::query()->create([
            'started_at' => now()->subMinutes(10),
            'finished_at' => now()->subMinutes(9),
            'duration_ms' => 1000,
            'status' => 'success',
            'source_url' => 'https://www.na.org.br/virtual/',
        ]);

        MetricRequestMetric::query()->create([
            'occurred_at' => now()->subMinutes(10),
            'route' => 'reunioes-virtuais',
            'http_method' => 'GET',
            'status_code' => 200,
            'duration_ms' => 100,
            'session_hash' => 's1',
            'ip_hash' => 'i1',
        ]);
        MetricRequestMetric::query()->create([
            'occurred_at' => now()->subMinutes(9),
            'route' => 'reunioes-virtuais',
            'http_method' => 'GET',
            'status_code' => 200,
            'duration_ms' => 600,
            'session_hash' => 's2',
            'ip_hash' => 'i2',
        ]);
        MetricRequestMetric::query()->create([
            'occurred_at' => now()->subMinutes(8),
            'route' => 'reunioes-virtuais',
            'http_method' => 'GET',
            'status_code' => 200,
            'duration_ms' => 900,
            'session_hash' => 's3',
            'ip_hash' => 'i3',
        ]);

        Http::fake([
            'https://alerts.local/metrics' => Http::response([], 200),
        ]);

        app(NaVirtualMeetingMetricsOperationalAlertService::class)->evaluateHealth(now());

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://alerts.local/metrics'
                && $request['type'] === 'high_latency'
                && $request['p95_latency_ms'] >= 500;
        });
    }

    public function test_deduplicates_same_alert_within_window(): void
    {
        config()->set('na_virtual.metrics.alerts.enabled', true);
        config()->set('na_virtual.metrics.alerts.webhook_url', 'https://alerts.local/metrics');
        config()->set('na_virtual.metrics.alerts.sync_stale_minutes_threshold', 60);
        config()->set('na_virtual.metrics.alerts.failed_run_recent_minutes', 1);
        config()->set('na_virtual.metrics.alerts.min_request_samples', 999);
        config()->set('na_virtual.metrics.alerts.dedupe_minutes', 30);
        config()->set('na_virtual.metrics.alerts.cache_prefix', 'na.virtual.metrics.alerts.test3');

        MetricSyncRun::query()->create([
            'started_at' => now()->subMinutes(200),
            'finished_at' => now()->subMinutes(199),
            'duration_ms' => 800,
            'status' => 'success',
            'source_url' => 'https://www.na.org.br/virtual/',
        ]);

        Http::fake([
            'https://alerts.local/metrics' => Http::response([], 200),
        ]);

        $service = app(NaVirtualMeetingMetricsOperationalAlertService::class);
        $service->evaluateHealth(now());
        $service->evaluateHealth(now()->addMinutes(5));

        Http::assertSentCount(1);
    }
}
