<?php

namespace App\Services;

use App\Models\MetricHourlyAggregate;
use App\Models\MetricMeetingSnapshot;
use App\Models\MetricPageView;
use App\Models\MetricRequestMetric;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class NaVirtualMeetingMetricsIngestionService
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function trackPageView(Request $request, string $route, array $context = []): void
    {
        if (! $this->metricsEnabled() || ! $this->tableExists('metric_page_views')) {
            return;
        }

        MetricPageView::query()->create([
            'event_uuid' => (string) Str::ulid(),
            'occurred_at' => now(),
            'route' => $route,
            'event_type' => 'page_view',
            'category' => null,
            'session_hash' => $this->hashValue($this->resolveSessionId($request)),
            'ip_hash' => $this->hashValue($request->ip()),
            'user_agent' => $this->truncateUserAgent($request->userAgent()),
            'context' => $context,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function trackEvent(Request $request, array $payload): void
    {
        if (! $this->metricsEnabled() || ! $this->tableExists('metric_page_views')) {
            return;
        }

        $eventType = is_string(data_get($payload, 'event_type')) ? (string) data_get($payload, 'event_type') : 'category_click';
        $category = is_string(data_get($payload, 'category')) ? trim((string) data_get($payload, 'category')) : null;

        MetricPageView::query()->create([
            'event_uuid' => (string) Str::ulid(),
            'occurred_at' => now(),
            'route' => is_string(data_get($payload, 'route')) ? (string) data_get($payload, 'route') : (string) $request->path(),
            'event_type' => $eventType,
            'category' => $category !== '' ? $category : null,
            'session_hash' => $this->hashValue($this->resolveSessionId($request)),
            'ip_hash' => $this->hashValue($request->ip()),
            'user_agent' => $this->truncateUserAgent($request->userAgent()),
            'context' => [
                'meeting_name' => data_get($payload, 'meeting_name'),
                'meeting_row_id' => data_get($payload, 'meeting_row_id'),
                'meeting_signature' => data_get($payload, 'meeting_signature'),
                'source_section' => data_get($payload, 'source_section'),
            ],
        ]);
    }

    public function captureMeetingSnapshot(?Carbon $measuredAt = null): void
    {
        if (! $this->metricsEnabled() || ! $this->tableExists('metric_meeting_snapshots')) {
            return;
        }

        $measuredAt = ($measuredAt ?? now())->copy();
        $dataset = app(NaVirtualMeetingGroupingService::class)->buildHomePageData($measuredAt);

        $startingSoon = collect(data_get($dataset, 'startingSoonMeetings', collect()));
        $upcoming = collect(data_get($dataset, 'upcomingMeetings', collect()));

        $within1h = $startingSoon
            ->filter(fn (array $item): bool => (int) data_get($item, 'starts_in_minutes', 0) <= 60)
            ->count();

        $within6h = $startingSoon
            ->merge($upcoming)
            ->filter(fn (array $item): bool => (int) data_get($item, 'starts_in_minutes', 0) <= 360)
            ->count();

        MetricMeetingSnapshot::query()->create([
            'measured_at' => $measuredAt,
            'in_progress_count' => (int) data_get($dataset, 'runningCount', 0),
            'within_1h_count' => $within1h,
            'within_6h_count' => $within6h,
        ]);
    }

    /**
     * @param  array{duration_ms?: int, status_code?: int}  $context
     */
    public function trackRequestMetric(Request $request, string $route, array $context = []): void
    {
        if (! $this->requestMetricsEnabled() || ! $this->tableExists('metric_request_metrics')) {
            return;
        }

        $durationMs = max(0, (int) data_get($context, 'duration_ms', 0));
        $statusCode = max(0, (int) data_get($context, 'status_code', 200));

        MetricRequestMetric::query()->create([
            'occurred_at' => now(),
            'route' => mb_substr($route, 0, 160),
            'http_method' => mb_substr((string) $request->getMethod(), 0, 10),
            'status_code' => $statusCode,
            'duration_ms' => $durationMs,
            'session_hash' => $this->hashValue($this->resolveSessionId($request)),
            'ip_hash' => $this->hashValue($request->ip()),
        ]);
    }

    public function consolidateHourlyAggregates(?Carbon $reference = null): void
    {
        if (! $this->metricsEnabled() || ! $this->tableExists('metric_request_metrics') || ! $this->tableExists('metric_hourly_aggregates')) {
            return;
        }

        $reference = ($reference ?? now())->copy();
        $hoursBack = max(1, (int) config('na_virtual.metrics.hourly_aggregates.hours_back', 48));
        $from = $reference->copy()->subHours($hoursBack)->startOfHour();

        $rows = MetricRequestMetric::query()
            ->where('occurred_at', '>=', $from)
            ->orderBy('occurred_at')
            ->get(['route', 'occurred_at', 'duration_ms']);

        /** @var Collection<string, Collection<int, MetricRequestMetric>> $grouped */
        $grouped = $rows->groupBy(function (MetricRequestMetric $row): string {
            $hourBucket = optional($row->occurred_at)?->copy()->startOfHour()->format('Y-m-d H:00:00') ?? now()->startOfHour()->format('Y-m-d H:00:00');

            return $hourBucket.'|'.($row->route ?: '/');
        });

        $payload = $grouped->map(function (Collection $items, string $groupKey): array {
            [$hourBucket, $route] = array_pad(explode('|', $groupKey, 2), 2, '/');
            $durations = $items
                ->pluck('duration_ms')
                ->map(fn ($value): int => (int) $value)
                ->sort()
                ->values()
                ->all();

            $count = count($durations);
            $avg = $count > 0 ? (int) round(array_sum($durations) / $count) : 0;
            $p95 = $this->percentile($durations, 95);

            return [
                'hour_bucket' => Carbon::parse($hourBucket),
                'metric_key' => 'request_latency',
                'dimension' => $route,
                'total_count' => $count,
                'avg_duration_ms' => $avg,
                'p95_duration_ms' => $p95,
                'updated_at' => now(),
                'created_at' => now(),
            ];
        })->values()->all();

        if ($payload === []) {
            return;
        }

        MetricHourlyAggregate::query()->upsert(
            $payload,
            ['hour_bucket', 'metric_key', 'dimension'],
            ['total_count', 'avg_duration_ms', 'p95_duration_ms', 'updated_at']
        );
    }

    private function metricsEnabled(): bool
    {
        return (bool) config('na_virtual.metrics.enabled', true);
    }
    private function requestMetricsEnabled(): bool
    {
        return $this->metricsEnabled() && (bool) config('na_virtual.metrics.request_metrics.enabled', true);
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
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $e) {
            Log::warning('Falha ao verificar tabela de metricas.', [
                'table' => $table,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function hashValue(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return hash('sha256', $value);
    }

    private function truncateUserAgent(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return mb_substr($value, 0, (int) config('na_virtual.metrics.events.user_agent_max_length', 255));
    }

    private function resolveSessionId(Request $request): ?string
    {
        if (! $request->hasSession()) {
            return null;
        }

        try {
            return $request->session()->getId();
        } catch (\Throwable) {
            return null;
        }
    }
}



