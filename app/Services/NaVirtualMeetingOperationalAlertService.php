<?php

namespace App\Services;

use App\Support\SensitiveDataMasker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class NaVirtualMeetingOperationalAlertService
{
    /**
     * @param  array<string, int|string>  $syncResult
     */
    public function handleSyncSuccess(array $syncResult, Carbon $timestamp): void
    {
        if (! $this->alertsEnabled()) {
            return;
        }

        $this->resetConsecutiveFailuresCounter();

        $activeBeforeCount = (int) ($syncResult['active_before_count'] ?? 0);
        $totalFound = (int) ($syncResult['total_found'] ?? 0);
        $dropPercentage = $this->calculateDropPercentage($activeBeforeCount, $totalFound);
        $dropThreshold = max(0.0, (float) config('na_virtual.alerts.volume_drop_percent_threshold', 60.0));
        $minActiveBase = max(1, (int) config('na_virtual.alerts.min_active_base_for_volume_alert', 50));

        $shouldAlert = $activeBeforeCount >= $minActiveBase && $dropPercentage >= $dropThreshold;
        $decision = $shouldAlert ? 'alert_dispatched' : 'alert_not_required';

        $context = [
            'type' => 'volume_drop',
            'timestamp' => $timestamp->toIso8601String(),
            'active_before_count' => $activeBeforeCount,
            'total_found' => $totalFound,
            'drop_percentage' => $dropPercentage,
            'consecutive_failures' => 0,
            'decision' => $decision,
        ];

        $this->alertLogger()->info('Avaliacao de alerta operacional de queda de volume.', SensitiveDataMasker::sanitizeContext($context));

        if ($shouldAlert) {
            $this->dispatchAlert(
                'volume_drop',
                'Queda brusca de volume detectada no sync de reunioes virtuais.',
                $context
            );
        }
    }

    public function handleSyncFailure(string $message, Carbon $timestamp): void
    {
        if (! $this->alertsEnabled()) {
            return;
        }

        $consecutiveFailures = $this->incrementConsecutiveFailuresCounter();
        $failureThreshold = max(1, (int) config('na_virtual.alerts.consecutive_failures_threshold', 3));
        $shouldAlert = $consecutiveFailures >= $failureThreshold;
        $decision = $shouldAlert ? 'alert_dispatched' : 'alert_suppressed_until_threshold';

        $context = [
            'type' => 'consecutive_failures',
            'timestamp' => $timestamp->toIso8601String(),
            'active_before_count' => null,
            'total_found' => null,
            'drop_percentage' => null,
            'consecutive_failures' => $consecutiveFailures,
            'decision' => $decision,
            'error_message' => SensitiveDataMasker::sanitizeText($message),
        ];

        $this->alertLogger()->warning('Avaliacao de alerta operacional por falhas consecutivas.', SensitiveDataMasker::sanitizeContext($context));

        if ($shouldAlert) {
            $this->dispatchAlert(
                'consecutive_failures',
                'Threshold de falhas consecutivas atingido no sync de reunioes virtuais.',
                $context
            );
        }
    }

    private function dispatchAlert(string $type, string $message, array $context): void
    {
        $payload = SensitiveDataMasker::sanitizeContext([
            'type' => $type,
            'message' => $message,
            ...$context,
        ]);

        $this->alertLogger()->critical($message, $payload);

        $webhookUrl = trim((string) config('na_virtual.alerts.webhook_url', ''));
        if ($webhookUrl === '') {
            return;
        }

        try {
            Http::timeout(10)->post($webhookUrl, $payload);
        } catch (Throwable $e) {
            $this->alertLogger()->error('Falha ao enviar alerta operacional para webhook.', [
                'type' => $type,
                'message' => SensitiveDataMasker::sanitizeText($e->getMessage()),
                'webhook_host' => parse_url($webhookUrl, PHP_URL_HOST),
            ]);
        }
    }

    private function calculateDropPercentage(int $activeBeforeCount, int $totalFound): float
    {
        if ($activeBeforeCount <= 0) {
            return 0.0;
        }

        $foundRatio = $totalFound / $activeBeforeCount;

        return round(max(0, (1 - $foundRatio) * 100), 2);
    }

    private function alertsEnabled(): bool
    {
        return (bool) config('na_virtual.alerts.enabled', true);
    }

    private function incrementConsecutiveFailuresCounter(): int
    {
        $cacheKey = (string) config('na_virtual.alerts.consecutive_failures_cache_key', 'na.virtual.alerts.consecutive_failures');
        $current = (int) Cache::get($cacheKey, 0);
        $next = $current + 1;
        Cache::forever($cacheKey, $next);

        return $next;
    }

    private function resetConsecutiveFailuresCounter(): void
    {
        $cacheKey = (string) config('na_virtual.alerts.consecutive_failures_cache_key', 'na.virtual.alerts.consecutive_failures');
        Cache::forever($cacheKey, 0);
    }

    private function alertLogger(): \Psr\Log\LoggerInterface
    {
        $channel = (string) config('na_virtual.alerts.channel', 'na_virtual_alerts');

        try {
            return Log::channel($channel);
        } catch (Throwable) {
            return Log::channel('stack');
        }
    }
}
