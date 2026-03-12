<?php

namespace App\Services;

use App\Models\VirtualMeetingSnapshot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class NaVirtualMeetingSnapshotService
{
    /**
     * Persist homepage snapshot using retention and deduplication by payload hash.
     *
     * @param  array<string, mixed>  $homePageData
     */
    public function saveHomepageSnapshot(array $homePageData, ?Carbon $capturedAt = null): void
    {
        if (! $this->snapshotTableExists()) {
            Log::warning('Tabela de snapshots nao encontrada; persistencia de snapshot ignorada.', [
                'table' => 'virtual_meeting_snapshots',
            ]);

            return;
        }

        $capturedAt = ($capturedAt ?? now())->copy();
        $context = (string) config('na_virtual.snapshot.context_homepage', 'na.virtual.homepage');
        $payload = $this->serializeHomePageData($homePageData);
        $payloadHash = sha1((string) json_encode($payload));

        $latest = VirtualMeetingSnapshot::query()
            ->where('context', $context)
            ->orderByDesc('captured_at')
            ->first();

        if ($latest !== null && $latest->payload_hash === $payloadHash) {
            $latest->forceFill([
                'captured_at' => $capturedAt,
            ])->save();
        } else {
            VirtualMeetingSnapshot::query()->create([
                'context' => $context,
                'payload' => $payload,
                'payload_hash' => $payloadHash,
                'captured_at' => $capturedAt,
            ]);
        }

        $this->trimSnapshots($context);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getLatestHomepageSnapshotData(): ?array
    {
        if (! $this->snapshotTableExists()) {
            return null;
        }

        $context = (string) config('na_virtual.snapshot.context_homepage', 'na.virtual.homepage');

        $snapshot = VirtualMeetingSnapshot::query()
            ->where('context', $context)
            ->orderByDesc('captured_at')
            ->first();

        if ($snapshot === null || ! is_array($snapshot->payload)) {
            return null;
        }

        return $this->deserializeHomePageData($snapshot->payload);
    }

    private function trimSnapshots(string $context): void
    {
        $maxRecords = max(1, (int) config('na_virtual.snapshot.max_records', 50));

        $orderedIds = VirtualMeetingSnapshot::query()
            ->where('context', $context)
            ->orderByDesc('captured_at')
            ->pluck('id');

        $staleIds = $orderedIds->slice($maxRecords)->values();

        if ($staleIds->isNotEmpty()) {
            VirtualMeetingSnapshot::query()
                ->whereIn('id', $staleIds)
                ->delete();
        }
    }

    private function snapshotTableExists(): bool
    {
        return Schema::hasTable('virtual_meeting_snapshots');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function serializeHomePageData(array $data): array
    {
        return [
            'serverTime' => data_get($data, 'serverTime') instanceof Carbon
                ? data_get($data, 'serverTime')->toIso8601String()
                : null,
            'runningCount' => (int) data_get($data, 'runningCount', 0),
            'startingSoonCount' => (int) data_get($data, 'startingSoonCount', 0),
            'upcomingCount' => (int) data_get($data, 'upcomingCount', 0),
            'runningMeetings' => $this->serializeMeetingItems(data_get($data, 'runningMeetings', collect())),
            'startingSoonMeetings' => $this->serializeMeetingItems(data_get($data, 'startingSoonMeetings', collect())),
            'upcomingMeetings' => $this->serializeMeetingItems(data_get($data, 'upcomingMeetings', collect())),
            'groupedBadges' => is_array(data_get($data, 'groupedBadges')) ? data_get($data, 'groupedBadges') : [],
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>|array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function serializeMeetingItems(Collection|array $items): array
    {
        $collection = $items instanceof Collection ? $items : collect($items);

        return $collection
            ->map(function (array $item): array {
                $meeting = data_get($item, 'meeting');

                return [
                    'meeting' => $meeting ? [
                        'name' => $meeting->name,
                        'meeting_platform' => $meeting->meeting_platform,
                        'meeting_url' => $meeting->meeting_url,
                        'type_label' => $meeting->type_label,
                        'format_labels' => is_array($meeting->format_labels) ? $meeting->format_labels : [],
                    ] : null,
                    'start_at' => data_get($item, 'start_at') instanceof Carbon ? data_get($item, 'start_at')->toIso8601String() : null,
                    'end_at' => data_get($item, 'end_at') instanceof Carbon ? data_get($item, 'end_at')->toIso8601String() : null,
                    'starts_in_minutes' => (int) data_get($item, 'starts_in_minutes', 0),
                    'ends_in_minutes' => (int) data_get($item, 'ends_in_minutes', 0),
                    'status_text' => data_get($item, 'status_text'),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function deserializeHomePageData(array $payload): array
    {
        return [
            'serverTime' => is_string(data_get($payload, 'serverTime')) ? Carbon::parse((string) data_get($payload, 'serverTime')) : now(),
            'runningCount' => (int) data_get($payload, 'runningCount', 0),
            'startingSoonCount' => (int) data_get($payload, 'startingSoonCount', 0),
            'upcomingCount' => (int) data_get($payload, 'upcomingCount', 0),
            'runningMeetings' => $this->deserializeMeetingItems(data_get($payload, 'runningMeetings', [])),
            'startingSoonMeetings' => $this->deserializeMeetingItems(data_get($payload, 'startingSoonMeetings', [])),
            'upcomingMeetings' => $this->deserializeMeetingItems(data_get($payload, 'upcomingMeetings', [])),
            'groupedBadges' => is_array(data_get($payload, 'groupedBadges')) ? data_get($payload, 'groupedBadges') : [],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return Collection<int, array<string, mixed>>
     */
    private function deserializeMeetingItems(array $items): Collection
    {
        return collect($items)->map(function (array $item): array {
            $meeting = data_get($item, 'meeting');

            return [
                'meeting' => is_array($meeting) ? (object) $meeting : null,
                'start_at' => is_string(data_get($item, 'start_at')) ? Carbon::parse((string) data_get($item, 'start_at')) : null,
                'end_at' => is_string(data_get($item, 'end_at')) ? Carbon::parse((string) data_get($item, 'end_at')) : null,
                'starts_in_minutes' => (int) data_get($item, 'starts_in_minutes', 0),
                'ends_in_minutes' => (int) data_get($item, 'ends_in_minutes', 0),
                'status_text' => data_get($item, 'status_text'),
            ];
        })->values();
    }
}
