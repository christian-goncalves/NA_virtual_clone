<?php

namespace App\Services;

use App\Models\MetricMeetingSnapshot;
use App\Models\MetricPageView;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
            'occurred_at' => now(),
            'route' => is_string(data_get($payload, 'route')) ? (string) data_get($payload, 'route') : (string) $request->path(),
            'event_type' => $eventType,
            'category' => $category !== '' ? $category : null,
            'session_hash' => $this->hashValue($this->resolveSessionId($request)),
            'ip_hash' => $this->hashValue($request->ip()),
            'user_agent' => $this->truncateUserAgent($request->userAgent()),
            'context' => [
                'meeting_name' => data_get($payload, 'meeting_name'),
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

    private function metricsEnabled(): bool
    {
        return (bool) config('na_virtual.metrics.enabled', true);
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
