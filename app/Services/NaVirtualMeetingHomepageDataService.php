<?php

namespace App\Services;

use App\Models\VirtualMeeting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NaVirtualMeetingHomepageDataService
{
    public function __construct(
        private readonly NaVirtualMeetingGroupingService $groupingService,
        private readonly NaVirtualMeetingSnapshotService $snapshotService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildForHomepage(): array
    {
        $liveData = $this->groupingService->buildHomePageData();
        $snapshotData = $this->snapshotService->getLatestHomepageSnapshotData();

        if ($this->shouldUseSnapshot($liveData, $snapshotData)) {
            Log::warning('Fallback da homepage acionado com ultimo snapshot valido.', [
                'reason' => $this->resolveFallbackReason($liveData),
                'last_success_at' => optional($this->resolveLastSuccessfulSyncAt())?->toIso8601String(),
                'last_failure_at' => optional($this->resolveLastFailureSyncAt())?->toIso8601String(),
            ]);

            // Keep snapshot data, but always expose current server time so the header clock
            // and time-based labels do not appear frozen during fallback windows.
            $snapshotData['serverTime'] = now();

            return $snapshotData;
        }

        return $liveData;
    }

    /**
     * @param  array<string, mixed>  $liveData
     * @param  array<string, mixed>|null  $snapshotData
     */
    private function shouldUseSnapshot(array $liveData, ?array $snapshotData): bool
    {
        if ($snapshotData === null) {
            return false;
        }

        $totalLive = (int) data_get($liveData, 'runningCount', 0)
            + (int) data_get($liveData, 'startingSoonCount', 0)
            + (int) data_get($liveData, 'upcomingCount', 0);

        if ($totalLive === 0) {
            return true;
        }

        return $this->isSyncUnavailableOrStale();
    }

    private function resolveFallbackReason(array $liveData): string
    {
        $totalLive = (int) data_get($liveData, 'runningCount', 0)
            + (int) data_get($liveData, 'startingSoonCount', 0)
            + (int) data_get($liveData, 'upcomingCount', 0);

        if ($totalLive === 0) {
            return 'live_dataset_empty';
        }

        return 'sync_unavailable_or_stale';
    }

    private function isSyncUnavailableOrStale(): bool
    {
        $lastSuccessAt = $this->resolveLastSuccessfulSyncAt();
        $lastFailureAt = $this->resolveLastFailureSyncAt();

        if ($lastFailureAt !== null && ($lastSuccessAt === null || $lastFailureAt->greaterThan($lastSuccessAt))) {
            return true;
        }

        if ($lastSuccessAt === null) {
            return true;
        }

        $maxStaleMinutes = max(1, (int) config('na_virtual.homepage_fallback.max_stale_minutes', 180));

        return $lastSuccessAt->diffInMinutes(now()) > $maxStaleMinutes;
    }

    private function resolveLastSuccessfulSyncAt(): ?Carbon
    {
        $cacheKey = (string) config('na_virtual.sync_status.last_success_cache_key', 'na.virtual.sync.last_success_at');
        $cachedValue = Cache::get($cacheKey);
        $parsed = $this->parseCacheDateValue($cachedValue);

        if ($parsed !== null) {
            return $parsed;
        }

        $latest = VirtualMeeting::query()->max('synced_at');

        return $latest ? Carbon::parse((string) $latest) : null;
    }

    private function resolveLastFailureSyncAt(): ?Carbon
    {
        $cacheKey = (string) config('na_virtual.sync_status.last_failure_cache_key', 'na.virtual.sync.last_failure_at');

        return $this->parseCacheDateValue(Cache::get($cacheKey));
    }

    private function parseCacheDateValue(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value->copy();
        }

        if (is_string($value) && trim($value) !== '') {
            return Carbon::parse($value);
        }

        return null;
    }
}

