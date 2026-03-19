<?php

namespace App\Services;

use App\Models\MetricRequestMetric;
use App\Models\MetricSyncRun;
use App\Support\SensitiveDataMasker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class NaVirtualMeetingMetricsOperationalAlertService
{
    /**
     * @return array<string, mixed>
     */
    public function evaluateHealth(?Carbon $reference = null): array
    {
        if (! $this->alertsEnabled()) {
            return [
                'enabled' => false,
                'alerts' => [],
            ];
        }

        $reference = ($reference ?? now())->copy();
        $alerts = [];

        $syncStale = $this->evaluateSyncStale($reference);
        if ($syncStale !== null) {
            $alerts[] = $syncStale;
        }

        $syncFailed = $this->evaluateRecentSyncFailure($reference);
        if ($syncFailed !== null) {
            $alerts[] = $syncFailed;
        }

        $highLatency = $this->evaluateHighLatency($reference);
        if ($highLatency !== null) {
            $alerts[] = $highLatency;
        }

        return [
            'enabled' => true,
            'alerts' => $alerts,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function evaluateSyncStale(Carbon $reference): ?array
    {
        if (! Schema::hasTable('metric_sync_runs')) {
            return null;
        }

        $maxStaleMinutes = max(1, (int) config('na_virtual.metrics.alerts.sync_stale_minutes_threshold', 90));
        $latestSuccessAt = MetricSyncRun::query()
            ->where('status', 'success')
            ->latest('started_at')
            ->value('started_at');

        if (! $latestSuccessAt) {
            return $this->dispatchAlert(
                'sync_stale',
                'Nenhuma sincronizacao bem-sucedida foi registrada para o dashboard de metricas.',
                [
                    'latest_success_at' => null,
                    'max_stale_minutes' => $maxStaleMinutes,
                    'evaluated_at' => $reference->toIso8601String(),
                ]
            );
        }

        $latestSuccess = Carbon::parse($latestSuccessAt);
        if ($latestSuccess->diffInMinutes($reference) < $maxStaleMinutes) {
            return null;
        }

        return $this->dispatchAlert(
            'sync_stale',
            'Sincronizacao desatualizada detectada no dashboard de metricas.',
            [
                'latest_success_at' => $latestSuccess->toIso8601String(),
                'max_stale_minutes' => $maxStaleMinutes,
                'evaluated_at' => $reference->toIso8601String(),
            ]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function evaluateRecentSyncFailure(Carbon $reference): ?array
    {
        if (! Schema::hasTable('metric_sync_runs')) {
            return null;
        }

        $recentMinutes = max(1, (int) config('na_virtual.metrics.alerts.failed_run_recent_minutes', 45));
        $latestRun = MetricSyncRun::query()->latest('started_at')->first();

        if ($latestRun === null || $latestRun->status !== 'failed' || $latestRun->started_at === null) {
            return null;
        }

        $failedAt = $latestRun->started_at->copy();
        if ($failedAt->diffInMinutes($reference) > $recentMinutes) {
            return null;
        }

        return $this->dispatchAlert(
            'sync_failed_recent',
            'Falha recente de sincronizacao detectada no dashboard de metricas.',
            [
                'latest_run_status' => $latestRun->status,
                'latest_run_at' => $failedAt->toIso8601String(),
                'recent_minutes_window' => $recentMinutes,
                'evaluated_at' => $reference->toIso8601String(),
            ]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function evaluateHighLatency(Carbon $reference): ?array
    {
        if (! Schema::hasTable('metric_request_metrics')) {
            return null;
        }

        $windowMinutes = max(1, (int) config('na_virtual.metrics.alerts.latency_window_minutes', 60));
        $minSamples = max(1, (int) config('na_virtual.metrics.alerts.min_request_samples', 20));
        $thresholdP95 = max(1, (int) config('na_virtual.metrics.alerts.latency_p95_threshold_ms', 2500));
        $from = $reference->copy()->subMinutes($windowMinutes);

        $durations = MetricRequestMetric::query()
            ->where('occurred_at', '>=', $from)
            ->orderBy('duration_ms')
            ->pluck('duration_ms')
            ->map(fn ($value): int => (int) $value)
            ->values()
            ->all();

        if (count($durations) < $minSamples) {
            return null;
        }

        $p95 = $this->percentile($durations, 95);
        if ($p95 < $thresholdP95) {
            return null;
        }

        return $this->dispatchAlert(
            'high_latency',
            'P95 de latencia acima do limite configurado no dashboard de metricas.',
            [
                'window_minutes' => $windowMinutes,
                'sample_count' => count($durations),
                'p95_latency_ms' => $p95,
                'threshold_p95_ms' => $thresholdP95,
                'evaluated_at' => $reference->toIso8601String(),
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>|null
     */
    private function dispatchAlert(string $type, string $message, array $context): ?array
    {
        if (! $this->canDispatchAlert($type)) {
            return null;
        }

        $payload = SensitiveDataMasker::sanitizeContext([
            'type' => $type,
            'message' => $message,
            ...$context,
            'source' => 'na_virtual.metrics',
        ]);

        $this->alertLogger()->critical($message, $payload);

        $webhookUrl = trim((string) config('na_virtual.metrics.alerts.webhook_url', ''));
        if ($webhookUrl !== '') {
            try {
                Http::timeout(10)->post($webhookUrl, $payload);
            } catch (Throwable $e) {
                $this->alertLogger()->error('Falha ao enviar alerta de metricas para webhook.', [
                    'type' => $type,
                    'message' => SensitiveDataMasker::sanitizeText($e->getMessage()),
                    'webhook_host' => parse_url($webhookUrl, PHP_URL_HOST),
                ]);
            }
        }

        return $payload;
    }

    private function alertsEnabled(): bool
    {
        return (bool) config('na_virtual.metrics.alerts.enabled', true);
    }

    private function canDispatchAlert(string $type): bool
    {
        $dedupeMinutes = max(1, (int) config('na_virtual.metrics.alerts.dedupe_minutes', 30));
        $cachePrefix = (string) config('na_virtual.metrics.alerts.cache_prefix', 'na.virtual.metrics.alerts.last_sent');
        $cacheKey = $cachePrefix.'.'.$type;
        $lastSent = Cache::get($cacheKey);
        $now = now();

        if (is_string($lastSent)) {
            $lastSentAt = Carbon::parse($lastSent);
            if ($lastSentAt->diffInMinutes($now) < $dedupeMinutes) {
                return false;
            }
        }

        Cache::forever($cacheKey, $now->toIso8601String());

        return true;
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

    private function alertLogger(): \Psr\Log\LoggerInterface
    {
        $channel = (string) config('na_virtual.metrics.alerts.channel', 'na_virtual_alerts');

        try {
            return Log::channel($channel);
        } catch (Throwable) {
            return Log::channel('stack');
        }
    }
}
