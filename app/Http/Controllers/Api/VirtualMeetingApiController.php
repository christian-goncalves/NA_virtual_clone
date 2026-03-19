<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NaVirtualMeetingHomepageDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class VirtualMeetingApiController extends Controller
{
    public function index(NaVirtualMeetingHomepageDataService $service): JsonResponse
    {
        $data = $service->buildForHomepage();

        return response()->json($this->normalizePayload($data));
    }

    public function serverTime(): JsonResponse
    {
        return response()->json([
            'serverTime' => now()->toIso8601String(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizePayload(array $payload): array
    {
        return [
            'serverTime' => $this->normalizeDateValue(data_get($payload, 'serverTime')),
            'runningCount' => (int) data_get($payload, 'runningCount', 0),
            'startingSoonCount' => (int) data_get($payload, 'startingSoonCount', 0),
            'upcomingCount' => (int) data_get($payload, 'upcomingCount', 0),
            'runningMeetings' => $this->normalizeMeetingItems(data_get($payload, 'runningMeetings', [])),
            'startingSoonMeetings' => $this->normalizeMeetingItems(data_get($payload, 'startingSoonMeetings', [])),
            'upcomingMeetings' => $this->normalizeMeetingItems(data_get($payload, 'upcomingMeetings', [])),
            'groupedBadges' => is_array(data_get($payload, 'groupedBadges')) ? data_get($payload, 'groupedBadges') : [],
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>|array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function normalizeMeetingItems(Collection|array $items): array
    {
        $collection = $items instanceof Collection ? $items : collect($items);

        return $collection
            ->map(function (array $item): array {
                $meeting = data_get($item, 'meeting');

                return [
                    'meeting' => $meeting ? [
                        'name' => data_get($meeting, 'name'),
                        'meeting_platform' => data_get($meeting, 'meeting_platform'),
                        'meeting_url' => data_get($meeting, 'meeting_url'),
                        'type_label' => data_get($meeting, 'type_label'),
                        'format_labels' => is_array(data_get($meeting, 'format_labels')) ? data_get($meeting, 'format_labels') : [],
                    ] : null,
                    'start_at' => $this->normalizeDateValue(data_get($item, 'start_at')),
                    'end_at' => $this->normalizeDateValue(data_get($item, 'end_at')),
                    'starts_in_minutes' => (int) data_get($item, 'starts_in_minutes', 0),
                    'ends_in_minutes' => (int) data_get($item, 'ends_in_minutes', 0),
                    'status_text' => data_get($item, 'status_text'),
                ];
            })
            ->values()
            ->all();
    }

    private function normalizeDateValue(mixed $value): ?string
    {
        if ($value instanceof Carbon) {
            return $value->toIso8601String();
        }

        if (is_string($value) && trim($value) !== '') {
            return Carbon::parse($value)->toIso8601String();
        }

        return null;
    }
}

