<?php

namespace Tests\Feature;

use App\Models\MetricRequestMetric;
use App\Services\NaVirtualMeetingMetricsIngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MetricsHourlyAggregationTest extends TestCase
{
    use RefreshDatabase;

    public function test_hourly_aggregation_consolidates_request_latency_by_route(): void
    {
        $reference = Carbon::create(2026, 3, 19, 10, 30, 0);

        MetricRequestMetric::query()->create([
            'occurred_at' => $reference->copy()->startOfHour()->addMinutes(5),
            'route' => 'reunioes-virtuais',
            'http_method' => 'GET',
            'status_code' => 200,
            'duration_ms' => 100,
            'session_hash' => 's1',
            'ip_hash' => 'i1',
        ]);

        MetricRequestMetric::query()->create([
            'occurred_at' => $reference->copy()->startOfHour()->addMinutes(10),
            'route' => 'reunioes-virtuais',
            'http_method' => 'GET',
            'status_code' => 200,
            'duration_ms' => 200,
            'session_hash' => 's2',
            'ip_hash' => 'i2',
        ]);

        MetricRequestMetric::query()->create([
            'occurred_at' => $reference->copy()->startOfHour()->addMinutes(20),
            'route' => 'reunioes-virtuais',
            'http_method' => 'GET',
            'status_code' => 200,
            'duration_ms' => 1000,
            'session_hash' => 's3',
            'ip_hash' => 'i3',
        ]);

        MetricRequestMetric::query()->create([
            'occurred_at' => $reference->copy()->startOfHour()->addMinutes(25),
            'route' => 'api/reunioes-virtuais',
            'http_method' => 'GET',
            'status_code' => 200,
            'duration_ms' => 320,
            'session_hash' => 's4',
            'ip_hash' => 'i4',
        ]);

        app(NaVirtualMeetingMetricsIngestionService::class)->consolidateHourlyAggregates($reference);

        $this->assertDatabaseHas('metric_hourly_aggregates', [
            'metric_key' => 'request_latency',
            'dimension' => 'reunioes-virtuais',
            'total_count' => 3,
            'avg_duration_ms' => 433,
            'p95_duration_ms' => 1000,
        ]);

        $this->assertDatabaseHas('metric_hourly_aggregates', [
            'metric_key' => 'request_latency',
            'dimension' => 'api/reunioes-virtuais',
            'total_count' => 1,
            'avg_duration_ms' => 320,
            'p95_duration_ms' => 320,
        ]);
    }
}
