<?php

namespace App\Services;

use App\Models\MetricMeetingSnapshot;
use App\Models\MetricPageView;
use App\Models\MetricSyncRun;
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
                'hourlyAccesses' => $this->hourlyAccesses(),
                'categoryClicks' => $this->categoryClicks(),
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

    private function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }
}
