<?php

namespace App\Services;

use App\Models\VirtualMeeting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NaVirtualMeetingGroupingService
{
    /**
     * Build grouped dataset for public page.
     *
     * @return array<string, mixed>
     */
    public function buildHomePageData(?Carbon $serverTime = null, int $startingSoonWindowMinutes = 60): array
    {
        $serverTime = ($serverTime ?? now())->copy();
        $grouped = $this->groupMeetings($serverTime, $startingSoonWindowMinutes);

        return [
            'serverTime' => $serverTime,
            'runningCount' => $grouped['running']->count(),
            'startingSoonCount' => $grouped['startingSoon']->count(),
            'upcomingCount' => $grouped['upcoming']->count(),
            'runningMeetings' => $grouped['running'],
            'startingSoonMeetings' => $grouped['startingSoon'],
            'upcomingMeetings' => $grouped['upcoming'],
            'groupedBadges' => [
                'aberta' => 'público em geral',
                'fechada' => 'que tem ou acha que tem problema com drogas',
                'estudo' => 'estudo de literatura',
            ],
        ];
    }

    /**
     * @return array{running: Collection<int, array<string, mixed>>, startingSoon: Collection<int, array<string, mixed>>, upcoming: Collection<int, array<string, mixed>>}
     */
    public function groupMeetings(?Carbon $serverTime = null, int $startingSoonWindowMinutes = 60): array
    {
        $serverTime = ($serverTime ?? now())->copy();
        $meetings = VirtualMeeting::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $running = collect();
        $startingSoon = collect();
        $upcoming = collect();

        foreach ($meetings as $meeting) {
            $occurrence = $this->resolveOccurrence($meeting, $serverTime);

            if ($occurrence === null) {
                continue;
            }

            $payload = [
                'meeting' => $meeting,
                'start_at' => $occurrence['start_at'],
                'end_at' => $occurrence['end_at'],
                'starts_in_minutes' => $occurrence['starts_in_minutes'],
                'ends_in_minutes' => $occurrence['ends_in_minutes'],
                'status_text' => $occurrence['status_text'],
            ];

            if ($occurrence['is_running']) {
                $running->push($payload);

                continue;
            }

            if ($occurrence['starts_in_minutes'] <= $startingSoonWindowMinutes) {
                $startingSoon->push($payload);

                continue;
            }

            $upcoming->push($payload);
        }

        return [
            'running' => $running->sortByDesc('start_at')->values(),
            'startingSoon' => $startingSoon->sortBy('start_at')->values(),
            'upcoming' => $upcoming->sortBy('start_at')->values(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveOccurrence(VirtualMeeting $meeting, Carbon $serverTime): ?array
    {
        if ($meeting->start_time === null) {
            return null;
        }

        $timezone = (string) ($meeting->timezone ?: 'America/Sao_Paulo');
        $now = $serverTime->copy()->setTimezone($timezone);
        $startTime = $this->normalizeTime((string) $meeting->start_time);
        $endTime = $this->normalizeTime($meeting->end_time ? (string) $meeting->end_time : null);
        $durationMinutes = $meeting->duration_minutes ? (int) $meeting->duration_minutes : 120;

        $candidates = $this->buildOccurrenceCandidates($meeting, $now, $startTime, $endTime, $durationMinutes);
        if ($candidates === []) {
            return null;
        }

        foreach ($candidates as $candidate) {
            if ($now->betweenIncluded($candidate['start_at'], $candidate['end_at'])) {
                $endsIn = (int) $now->diffInMinutes($candidate['end_at'], true);

                return [
                    'start_at' => $candidate['start_at'],
                    'end_at' => $candidate['end_at'],
                    'starts_in_minutes' => 0,
                    'ends_in_minutes' => $endsIn,
                    'is_running' => true,
                    'status_text' => 'termina em ' . $this->formatMinutesForStatus($endsIn),
                ];
            }
        }

        $next = collect($candidates)
            ->filter(fn (array $candidate): bool => $candidate['start_at']->greaterThan($now))
            ->sortBy('start_at')
            ->first();

        if ($next === null) {
            return null;
        }

        $startsIn = (int) $now->diffInMinutes($next['start_at'], true);

        return [
            'start_at' => $next['start_at'],
            'end_at' => $next['end_at'],
            'starts_in_minutes' => $startsIn,
            'ends_in_minutes' => (int) $now->diffInMinutes($next['end_at'], true),
            'is_running' => false,
            'status_text' => 'em ' . $this->formatMinutesForStatus($startsIn),
        ];
    }

    /**
     * @return list<array{start_at: Carbon, end_at: Carbon}>
     */
    private function buildOccurrenceCandidates(
        VirtualMeeting $meeting,
        Carbon $now,
        string $startTime,
        ?string $endTime,
        int $durationMinutes
    ): array {
        $weekday = $this->weekdayToCarbonDay($meeting->weekday ? (string) $meeting->weekday : null);

        if ($weekday === null) {
            return $this->buildDailyCandidates($now, $startTime, $endTime, $durationMinutes);
        }

        $base = $now->copy()->startOfWeek(Carbon::SUNDAY)->addDays($weekday)->startOfDay();
        $candidates = [];

        foreach ([-7, 0, 7] as $offsetDays) {
            $startAt = $base->copy()->addDays($offsetDays)->setTimeFromTimeString($startTime);
            $endAt = $this->resolveEndAt($startAt, $endTime, $durationMinutes);
            $candidates[] = [
                'start_at' => $startAt,
                'end_at' => $endAt,
            ];
        }

        return $candidates;
    }

    /**
     * @return list<array{start_at: Carbon, end_at: Carbon}>
     */
    private function buildDailyCandidates(Carbon $now, string $startTime, ?string $endTime, int $durationMinutes): array
    {
        $candidates = [];

        foreach ([-1, 0, 1] as $offsetDays) {
            $startAt = $now->copy()->startOfDay()->addDays($offsetDays)->setTimeFromTimeString($startTime);
            $endAt = $this->resolveEndAt($startAt, $endTime, $durationMinutes);
            $candidates[] = [
                'start_at' => $startAt,
                'end_at' => $endAt,
            ];
        }

        return $candidates;
    }

    private function resolveEndAt(Carbon $startAt, ?string $endTime, int $durationMinutes): Carbon
    {
        if ($endTime === null) {
            return $startAt->copy()->addMinutes(max(1, $durationMinutes));
        }

        $endAt = $startAt->copy()->setTimeFromTimeString($endTime);

        if ($endAt->lessThanOrEqualTo($startAt)) {
            $endAt->addDay();
        }

        return $endAt;
    }

    private function normalizeTime(?string $time): ?string
    {
        if ($time === null || trim($time) === '') {
            return null;
        }

        $time = trim($time);

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
            return $time;
        }

        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return "{$time}:00";
        }

        return null;
    }

    private function weekdayToCarbonDay(?string $weekday): ?int
    {
        if ($weekday === null || trim($weekday) === '') {
            return null;
        }

        $normalized = Str::lower(Str::ascii(trim($weekday)));

        return match (true) {
            Str::startsWith($normalized, 'dom') => Carbon::SUNDAY,
            Str::startsWith($normalized, 'seg') => Carbon::MONDAY,
            Str::startsWith($normalized, 'ter') => Carbon::TUESDAY,
            Str::startsWith($normalized, 'qua') => Carbon::WEDNESDAY,
            Str::startsWith($normalized, 'qui') => Carbon::THURSDAY,
            Str::startsWith($normalized, 'sex') => Carbon::FRIDAY,
            Str::startsWith($normalized, 'sab') => Carbon::SATURDAY,
            default => null,
        };
    }

    private function formatMinutesForStatus(int $minutes): string
    {
        $minutes = max(0, $minutes);

        if ($minutes < 60) {
            return "{$minutes} min";
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return "{$hours}h {$remainingMinutes}min";
    }
}

