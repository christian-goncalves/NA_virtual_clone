<?php

namespace Tests\Feature;

use App\Models\MetricHourlyAggregate;
use App\Models\MetricMeetingSnapshot;
use App\Models\MetricPageView;
use App\Models\MetricRequestMetric;
use App\Models\MetricSyncRun;
use App\Services\NaVirtualMeetingMetricsRetentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MetricsRetentionPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_retention_prunes_old_metrics_records(): void
    {
        $reference = Carbon::create(2026, 3, 19, 12, 0, 0);

        config()->set('na_virtual.metrics.retention.enabled', true);
        config()->set('na_virtual.metrics.retention.page_views_days', 30);
        config()->set('na_virtual.metrics.retention.request_metrics_days', 30);
        config()->set('na_virtual.metrics.retention.sync_runs_days', 90);
        config()->set('na_virtual.metrics.retention.meeting_snapshots_days', 90);
        config()->set('na_virtual.metrics.retention.hourly_aggregates_days', 180);

        MetricPageView::query()->create([
            'occurred_at' => $reference->copy()->subDays(40),
            'route' => 'reunioes-virtuais',
            'event_type' => 'page_view',
            'category' => null,
            'session_hash' => 'old',
            'ip_hash' => 'old',
            'user_agent' => 'phpunit',
            'context' => [],
        ]);
        MetricPageView::query()->create([
            'occurred_at' => $reference->copy()->subDays(5),
            'route' => 'reunioes-virtuais',
            'event_type' => 'page_view',
            'category' => null,
            'session_hash' => 'new',
            'ip_hash' => 'new',
            'user_agent' => 'phpunit',
            'context' => [],
        ]);

        MetricRequestMetric::query()->create([
            'occurred_at' => $reference->copy()->subDays(35),
            'route' => 'reunioes-virtuais',
            'http_method' => 'GET',
            'status_code' => 200,
            'duration_ms' => 150,
            'session_hash' => 'old',
            'ip_hash' => 'old',
        ]);
        MetricRequestMetric::query()->create([
            'occurred_at' => $reference->copy()->subDays(3),
            'route' => 'reunioes-virtuais',
            'http_method' => 'GET',
            'status_code' => 200,
            'duration_ms' => 150,
            'session_hash' => 'new',
            'ip_hash' => 'new',
        ]);

        MetricSyncRun::query()->create([
            'started_at' => $reference->copy()->subDays(120),
            'finished_at' => $reference->copy()->subDays(120)->addMinute(),
            'duration_ms' => 1000,
            'status' => 'success',
            'source_url' => 'https://www.na.org.br/virtual/',
        ]);
        MetricSyncRun::query()->create([
            'started_at' => $reference->copy()->subDays(10),
            'finished_at' => $reference->copy()->subDays(10)->addMinute(),
            'duration_ms' => 1000,
            'status' => 'success',
            'source_url' => 'https://www.na.org.br/virtual/',
        ]);

        MetricMeetingSnapshot::query()->create([
            'measured_at' => $reference->copy()->subDays(120),
            'in_progress_count' => 1,
            'within_1h_count' => 1,
            'within_6h_count' => 1,
        ]);
        MetricMeetingSnapshot::query()->create([
            'measured_at' => $reference->copy()->subDays(10),
            'in_progress_count' => 1,
            'within_1h_count' => 1,
            'within_6h_count' => 1,
        ]);

        MetricHourlyAggregate::query()->create([
            'hour_bucket' => $reference->copy()->subDays(220)->startOfHour(),
            'metric_key' => 'request_latency',
            'dimension' => 'reunioes-virtuais',
            'total_count' => 10,
            'avg_duration_ms' => 100,
            'p95_duration_ms' => 200,
        ]);
        MetricHourlyAggregate::query()->create([
            'hour_bucket' => $reference->copy()->subDays(20)->startOfHour(),
            'metric_key' => 'request_latency',
            'dimension' => 'reunioes-virtuais',
            'total_count' => 10,
            'avg_duration_ms' => 100,
            'p95_duration_ms' => 200,
        ]);

        $result = app(NaVirtualMeetingMetricsRetentionService::class)->prune($reference);

        $this->assertSame(1, $result['metric_page_views']);
        $this->assertSame(1, $result['metric_request_metrics']);
        $this->assertSame(1, $result['metric_sync_runs']);
        $this->assertSame(1, $result['metric_meeting_snapshots']);
        $this->assertSame(1, $result['metric_hourly_aggregates']);

        $this->assertDatabaseCount('metric_page_views', 1);
        $this->assertDatabaseCount('metric_request_metrics', 1);
        $this->assertDatabaseCount('metric_sync_runs', 1);
        $this->assertDatabaseCount('metric_meeting_snapshots', 1);
        $this->assertDatabaseCount('metric_hourly_aggregates', 1);
    }
}
