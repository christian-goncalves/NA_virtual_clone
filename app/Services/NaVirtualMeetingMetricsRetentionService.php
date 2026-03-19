<?php

namespace App\Services;

use App\Models\MetricHourlyAggregate;
use App\Models\MetricMeetingSnapshot;
use App\Models\MetricPageView;
use App\Models\MetricRequestMetric;
use App\Models\MetricSyncRun;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class NaVirtualMeetingMetricsRetentionService
{
    /**
     * @return array<string, int>
     */
    public function prune(?Carbon $reference = null): array
    {
        if (! $this->retentionEnabled()) {
            return [
                'metric_page_views' => 0,
                'metric_request_metrics' => 0,
                'metric_sync_runs' => 0,
                'metric_meeting_snapshots' => 0,
                'metric_hourly_aggregates' => 0,
            ];
        }

        $reference = ($reference ?? now())->copy();

        return [
            'metric_page_views' => $this->prunePageViews($reference),
            'metric_request_metrics' => $this->pruneRequestMetrics($reference),
            'metric_sync_runs' => $this->pruneSyncRuns($reference),
            'metric_meeting_snapshots' => $this->pruneMeetingSnapshots($reference),
            'metric_hourly_aggregates' => $this->pruneHourlyAggregates($reference),
        ];
    }

    private function prunePageViews(Carbon $reference): int
    {
        if (! Schema::hasTable('metric_page_views')) {
            return 0;
        }

        $days = max(1, (int) config('na_virtual.metrics.retention.page_views_days', 30));
        $cutoff = $reference->copy()->subDays($days);

        return MetricPageView::query()->where('occurred_at', '<', $cutoff)->delete();
    }

    private function pruneRequestMetrics(Carbon $reference): int
    {
        if (! Schema::hasTable('metric_request_metrics')) {
            return 0;
        }

        $days = max(1, (int) config('na_virtual.metrics.retention.request_metrics_days', 30));
        $cutoff = $reference->copy()->subDays($days);

        return MetricRequestMetric::query()->where('occurred_at', '<', $cutoff)->delete();
    }

    private function pruneSyncRuns(Carbon $reference): int
    {
        if (! Schema::hasTable('metric_sync_runs')) {
            return 0;
        }

        $days = max(1, (int) config('na_virtual.metrics.retention.sync_runs_days', 90));
        $cutoff = $reference->copy()->subDays($days);

        return MetricSyncRun::query()->where('started_at', '<', $cutoff)->delete();
    }

    private function pruneMeetingSnapshots(Carbon $reference): int
    {
        if (! Schema::hasTable('metric_meeting_snapshots')) {
            return 0;
        }

        $days = max(1, (int) config('na_virtual.metrics.retention.meeting_snapshots_days', 90));
        $cutoff = $reference->copy()->subDays($days);

        return MetricMeetingSnapshot::query()->where('measured_at', '<', $cutoff)->delete();
    }

    private function pruneHourlyAggregates(Carbon $reference): int
    {
        if (! Schema::hasTable('metric_hourly_aggregates')) {
            return 0;
        }

        $days = max(1, (int) config('na_virtual.metrics.retention.hourly_aggregates_days', 180));
        $cutoff = $reference->copy()->subDays($days);

        return MetricHourlyAggregate::query()->where('hour_bucket', '<', $cutoff)->delete();
    }

    private function retentionEnabled(): bool
    {
        return (bool) config('na_virtual.metrics.retention.enabled', true);
    }
}
