<?php

namespace App\Services;

use App\Models\MetricHourlyAggregate;
use App\Models\MetricMeetingSnapshot;
use App\Models\MetricPageView;
use App\Models\MetricRequestMetric;
use App\Models\MetricSyncRun;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class NaVirtualMeetingMetricsService
{
    /**
     * @return array<string, mixed>
     */
    public function buildDashboardData(): array
    {
        $ttl = max(1, (int) config('na_virtual.metrics.dashboard_cache_ttl_seconds', 30));

        return Cache::remember('na.virtual.metrics.dashboard', now()->addSeconds($ttl), function (): array {
            return [
                'accessesToday' => $this->accessesToday(),
                'accessesLastHour' => $this->accessesLastHour(),
                'runningNow' => $this->runningNow(),
                'lastSyncRun' => $this->lastSyncRun(),
                'syncSuccessRate24h' => $this->syncSuccessRate24h(),
                'averageLatency24h' => $this->averageLatency24h(),
                'p95Latency24h' => $this->p95Latency24h(),
                'hourlyAccesses' => $this->hourlyAccesses(),
                'categoryClicks' => $this->categoryClicks(),
                'availabilityByHour' => $this->availabilityByHour(),
                'syncStatusByHour' => $this->syncStatusByHour(),
                'syncStatusTotals24h' => $this->syncStatusTotals24h(),
                'latencyByHour' => $this->latencyByHour(),
                'topSlowRoutes' => $this->topSlowRoutes(),
                'recentSyncRuns' => $this->recentSyncRuns(),
            ];
        });
    }

    private function accessesToday(): int
    {
        if (! $this->tableExists('metric_page_views')) {
            return 0;
        }

        return MetricPageView::query()
            ->where('event_type', 'page_view')
            ->whereDate('occurred_at', now()->toDateString())
            ->count();
    }

    private function accessesLastHour(): int
    {
        if (! $this->tableExists('metric_page_views')) {
            return 0;
        }

        return MetricPageView::query()
            ->where('event_type', 'page_view')
            ->where('occurred_at', '>=', now()->subHour())
            ->count();
    }

    private function runningNow(): int
    {
        if (! $this->tableExists('metric_meeting_snapshots')) {
            return 0;
        }

        return (int) MetricMeetingSnapshot::query()->latest('measured_at')->value('in_progress_count');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function lastSyncRun(): ?array
    {
        if (! $this->tableExists('metric_sync_runs')) {
            return null;
        }

        $run = MetricSyncRun::query()->latest('started_at')->first();
        if ($run === null) {
            return null;
        }

        return [
            'status' => $run->status,
            'started_at' => optional($run->started_at)?->toIso8601String(),
            'duration_ms' => $run->duration_ms,
            'meetings_found' => $run->meetings_found,
            'error_message' => $run->error_message,
        ];
    }

    private function syncSuccessRate24h(): float
    {
        if (! $this->tableExists('metric_sync_runs')) {
            return 0.0;
        }

        $query = MetricSyncRun::query()->where('started_at', '>=', now()->subDay());
        $total = (int) $query->count();

        if ($total === 0) {
            return 0.0;
        }

        $success = (int) MetricSyncRun::query()
            ->where('started_at', '>=', now()->subDay())
            ->where('status', 'success')
            ->count();

        return round(($success / $total) * 100, 2);
    }

    private function averageLatency24h(): float
    {
        if (! $this->tableExists('metric_request_metrics')) {
            return 0.0;
        }

        $average = MetricRequestMetric::query()
            ->where('occurred_at', '>=', now()->subDay())
            ->avg('duration_ms');

        return round((float) ($average ?? 0), 2);
    }

    private function p95Latency24h(): int
    {
        if (! $this->tableExists('metric_request_metrics')) {
            return 0;
        }

        $durations = MetricRequestMetric::query()
            ->where('occurred_at', '>=', now()->subDay())
            ->orderBy('duration_ms')
            ->pluck('duration_ms')
            ->map(fn ($value): int => (int) $value)
            ->values()
            ->all();

        return $this->percentile($durations, 95);
    }

    /**
     * @return list<array{label: string, total: int}>
     */
    private function hourlyAccesses(): array
    {
        if (! $this->tableExists('metric_page_views')) {
            return [];
        }

        return MetricPageView::query()
            ->where('event_type', 'page_view')
            ->where('occurred_at', '>=', now()->subDay())
            ->orderBy('occurred_at')
            ->get(['occurred_at'])
            ->groupBy(fn (MetricPageView $row): string => optional($row->occurred_at)?->format('Y-m-d H:00') ?? 'sem_data')
            ->map(fn ($rows, string $label): array => [
                'label' => $label,
                'total' => $rows->count(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{category: string, total: int}>
     */
    private function categoryClicks(): array
    {
        if (! $this->tableExists('metric_page_views')) {
            return [];
        }

        return MetricPageView::query()
            ->selectRaw('COALESCE(category, "sem_categoria") as category, COUNT(*) as total')
            ->where('event_type', 'category_click')
            ->where('occurred_at', '>=', now()->subDay())
            ->groupBy('category')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row): array => [
                'category' => (string) $row->category,
                'total' => (int) $row->total,
            ])
            ->all();
    }

    /**
     * @return list<array{label: string, running: int, within_1h: int, within_6h: int}>
     */
    private function availabilityByHour(): array
    {
        if (! $this->tableExists('metric_meeting_snapshots')) {
            return [];
        }

        return MetricMeetingSnapshot::query()
            ->where('measured_at', '>=', now()->subDay())
            ->orderBy('measured_at')
            ->get(['measured_at', 'in_progress_count', 'within_1h_count', 'within_6h_count'])
            ->groupBy(fn (MetricMeetingSnapshot $row): string => optional($row->measured_at)?->format('Y-m-d H:00') ?? 'sem_data')
            ->map(function (Collection $items, string $label): array {
                $count = max(1, $items->count());

                return [
                    'label' => $label,
                    'running' => (int) round($items->sum('in_progress_count') / $count),
                    'within_1h' => (int) round($items->sum('within_1h_count') / $count),
                    'within_6h' => (int) round($items->sum('within_6h_count') / $count),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{label: string, success: int, failed: int}>
     */
    private function syncStatusByHour(): array
    {
        if (! $this->tableExists('metric_sync_runs')) {
            return [];
        }

        return MetricSyncRun::query()
            ->where('started_at', '>=', now()->subDay())
            ->orderBy('started_at')
            ->get(['started_at', 'status'])
            ->groupBy(fn (MetricSyncRun $row): string => optional($row->started_at)?->format('Y-m-d H:00') ?? 'sem_data')
            ->map(function (Collection $items, string $label): array {
                return [
                    'label' => $label,
                    'success' => $items->where('status', 'success')->count(),
                    'failed' => $items->where('status', 'failed')->count(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{success: int, failed: int}
     */
    private function syncStatusTotals24h(): array
    {
        if (! $this->tableExists('metric_sync_runs')) {
            return [
                'success' => 0,
                'failed' => 0,
            ];
        }

        return [
            'success' => MetricSyncRun::query()
                ->where('started_at', '>=', now()->subDay())
                ->where('status', 'success')
                ->count(),
            'failed' => MetricSyncRun::query()
                ->where('started_at', '>=', now()->subDay())
                ->where('status', 'failed')
                ->count(),
        ];
    }

    /**
     * @return list<array{label: string, avg_ms: int, p95_ms: int}>
     */
    private function latencyByHour(): array
    {
        if ($this->tableExists('metric_hourly_aggregates')) {
            $rows = MetricHourlyAggregate::query()
                ->where('metric_key', 'request_latency')
                ->where('hour_bucket', '>=', now()->subDay()->startOfHour())
                ->orderBy('hour_bucket')
                ->get(['hour_bucket', 'avg_duration_ms', 'p95_duration_ms']);

            if ($rows->isNotEmpty()) {
                return $rows
                    ->groupBy(fn (MetricHourlyAggregate $row): string => optional($row->hour_bucket)?->format('Y-m-d H:00') ?? 'sem_data')
                    ->map(function (Collection $items, string $label): array {
                        $avgValues = $items->pluck('avg_duration_ms')->filter(fn ($value): bool => $value !== null)->map(fn ($value): int => (int) $value)->all();
                        $p95Values = $items->pluck('p95_duration_ms')->filter(fn ($value): bool => $value !== null)->map(fn ($value): int => (int) $value)->all();

                        return [
                            'label' => $label,
                            'avg_ms' => $avgValues !== [] ? (int) round(array_sum($avgValues) / count($avgValues)) : 0,
                            'p95_ms' => $p95Values !== [] ? max($p95Values) : 0,
                        ];
                    })
                    ->values()
                    ->all();
            }
        }

        if (! $this->tableExists('metric_request_metrics')) {
            return [];
        }

        return MetricRequestMetric::query()
            ->where('occurred_at', '>=', now()->subDay())
            ->orderBy('occurred_at')
            ->get(['occurred_at', 'duration_ms'])
            ->groupBy(fn (MetricRequestMetric $row): string => optional($row->occurred_at)?->format('Y-m-d H:00') ?? 'sem_data')
            ->map(function (Collection $rows, string $label): array {
                $durations = $rows->pluck('duration_ms')->map(fn ($value): int => (int) $value)->sort()->values()->all();
                $count = count($durations);

                return [
                    'label' => $label,
                    'avg_ms' => $count > 0 ? (int) round(array_sum($durations) / $count) : 0,
                    'p95_ms' => $this->percentile($durations, 95),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{route: string, avg_ms: int, p95_ms: int, total: int}>
     */
    private function topSlowRoutes(): array
    {
        if (! $this->tableExists('metric_request_metrics')) {
            return [];
        }

        return MetricRequestMetric::query()
            ->where('occurred_at', '>=', now()->subDay())
            ->orderBy('occurred_at')
            ->get(['route', 'duration_ms'])
            ->groupBy(fn (MetricRequestMetric $row): string => $row->route ?: '/')
            ->map(function (Collection $rows, string $route): array {
                $durations = $rows->pluck('duration_ms')->map(fn ($value): int => (int) $value)->sort()->values()->all();
                $total = count($durations);

                return [
                    'route' => $route,
                    'avg_ms' => $total > 0 ? (int) round(array_sum($durations) / $total) : 0,
                    'p95_ms' => $this->percentile($durations, 95),
                    'total' => $total,
                ];
            })
            ->sortByDesc('p95_ms')
            ->take(5)
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentSyncRuns(): array
    {
        if (! $this->tableExists('metric_sync_runs')) {
            return [];
        }

        return MetricSyncRun::query()
            ->latest('started_at')
            ->limit(10)
            ->get()
            ->map(fn (MetricSyncRun $run): array => [
                'started_at' => optional($run->started_at)?->format('Y-m-d H:i:s'),
                'status' => $run->status,
                'duration_ms' => $run->duration_ms,
                'meetings_found' => $run->meetings_found,
                'error_message' => $run->error_message,
            ])
            ->all();
    }

    /**
     * @param  list<int>  $sortedValues
     */
    private function percentile(array $sortedValues, int $percentile): int
    {
        $count = count($sortedValues);
        if ($count === 0) {
            return 0;
        }

        $percentile = max(1, min(100, $percentile));
        $position = (int) ceil(($percentile / 100) * $count) - 1;
        $index = max(0, min($count - 1, $position));

        return (int) ($sortedValues[$index] ?? 0);
    }

    private function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }
}
