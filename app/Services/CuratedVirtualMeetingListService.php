<?php

namespace App\Services;

use App\Enums\CuratedMeetingFormat;
use App\Models\VirtualMeeting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CuratedVirtualMeetingListService
{
    /** @var array<string, mixed> */
    private array $lastSummary = [];

    public function __construct(private readonly CuratedGroupSheetSourceService $sheetSource) {}

    /**
     * @return list<string>
     */
    public function weekdays(): array
    {
        return ['segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado', 'domingo'];
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws ValidationException
     */
    public function loadValidatedGroups(): array
    {
        $source = $this->sheetSource->loadMeetingIdNamePairs();
        /** @var list<array{meeting_id:string|null, group_name:string}> $pairs */
        $pairs = $source['pairs'];
        $activeMeetings = VirtualMeeting::query()->where('is_active', true)->get();

        $resolvedGroups = [];
        $conflicts = [];
        $reasonCounts = [
            'id_not_found' => 0,
            'name_mismatch' => 0,
            'ambiguous_match' => 0,
        ];

        foreach ($pairs as $pair) {
            $meetingIdHint = trim((string) data_get($pair, 'meeting_id', ''));
            $meetingIdHint = $meetingIdHint !== '' ? $meetingIdHint : null;
            $curatedName = trim((string) data_get($pair, 'group_name'));

            $matches = $activeMeetings
                ->filter(fn (VirtualMeeting $meeting): bool => $this->isNameCompatible((string) $meeting->name, $curatedName));

            if ($matches->isEmpty()) {
                $idCandidates = $meetingIdHint === null
                    ? collect()
                    : $activeMeetings->filter(fn (VirtualMeeting $meeting): bool => (string) $meeting->meeting_id === $meetingIdHint);

                $this->registerConflict($conflicts, $reasonCounts, [
                    'meeting_id' => $meetingIdHint,
                    'group_name' => $curatedName,
                    'reason' => 'name_mismatch',
                ], [
                    'meeting_id' => $meetingIdHint,
                    'group_name' => $curatedName,
                    'reason' => 'name_mismatch',
                    'candidate_names' => $idCandidates->pluck('name')->unique()->values()->all(),
                ]);
                continue;
            }

            if ($meetingIdHint !== null) {
                $matchesWithHintId = $matches->filter(fn (VirtualMeeting $meeting): bool => (string) $meeting->meeting_id === $meetingIdHint);
                if ($matchesWithHintId->isNotEmpty()) {
                    $matches = $matchesWithHintId;
                }
            }

            $distinctMatchedNames = $matches
                ->pluck('name')
                ->map(fn ($value): string => $this->normalizeNameForMatching((string) $value))
                ->filter(fn ($value): bool => $value !== '')
                ->unique()
                ->values();

            if ($distinctMatchedNames->count() > 1) {
                $this->registerConflict($conflicts, $reasonCounts, [
                    'meeting_id' => $meetingIdHint,
                    'group_name' => $curatedName,
                    'reason' => 'ambiguous_match',
                ], [
                    'meeting_id' => $meetingIdHint,
                    'group_name' => $curatedName,
                    'reason' => 'ambiguous_match',
                    'matched_names' => $matches->pluck('name')->unique()->values()->all(),
                ]);
                continue;
            }

            $resolvedMeetingId = $this->resolveMeetingId($matches, $meetingIdHint);
            $resolvedGroups[] = $this->buildGroupFromMatches($resolvedMeetingId, $curatedName, $matches);
        }

        usort($resolvedGroups, function (array $a, array $b): int {
            $cityCmp = strcmp((string) data_get($a, 'city', ''), (string) data_get($b, 'city', ''));
            if ($cityCmp !== 0) {
                return $cityCmp;
            }

            return strcmp((string) data_get($a, 'group_name', ''), (string) data_get($b, 'group_name', ''));
        });

        $this->lastSummary = [
            'total_sheet_rows' => (int) ($source['total_rows_read'] ?? 0),
            'total_sheet_valid_pairs' => (int) ($source['total_valid_pairs'] ?? count($pairs)),
            'resolved_count' => count($resolvedGroups),
            'conflicts_count' => count($conflicts),
            'conflicts_by_reason' => $reasonCounts,
            'conflicts' => $conflicts,
            'generated_at' => now()->toIso8601String(),
        ];

        if ($resolvedGroups === []) {
            throw ValidationException::withMessages([
                'sheet' => ['Nenhum grupo foi resolvido na base com nome curado.'],
            ]);
        }

        return $resolvedGroups;
    }

    /**
     * @return array<string, mixed>
     */
    public function lastSummary(): array
    {
        return $this->lastSummary;
    }

    /**
     * @param array<int, array<string,mixed>> $conflicts
     * @param array<string,int> $reasonCounts
     * @param array<string,mixed> $summaryPayload
     * @param array<string,mixed> $logPayload
     */
    private function registerConflict(array &$conflicts, array &$reasonCounts, array $summaryPayload, array $logPayload): void
    {
        $conflicts[] = $summaryPayload;
        $reason = (string) data_get($summaryPayload, 'reason', '');

        if ($reason !== '' && array_key_exists($reason, $reasonCounts)) {
            $reasonCounts[$reason]++;
        }

        Log::warning('db_match_conflict', $logPayload);
    }

    /**
     * @param  Collection<int, VirtualMeeting>  $matches
     * @return array<string, mixed>
     */
    private function buildGroupFromMatches(string $meetingId, string $curatedName, Collection $matches): array
    {
        $city = $this->resolveMostFrequentText($matches->pluck('city')->filter(fn ($value) => is_string($value) && trim($value) !== '')->map(fn ($value) => trim((string) $value)));
        $linkUrl = $this->resolveMostFrequentText($matches->pluck('meeting_url')->filter(fn ($value) => is_string($value) && trim($value) !== '')->map(fn ($value) => trim((string) $value)));

        $schedule = array_fill_keys($this->weekdays(), []);

        foreach ($matches as $meeting) {
            $weekdayKey = $this->normalizeWeekday((string) $meeting->weekday);
            if ($weekdayKey === null) {
                continue;
            }

            $start = $this->formatTime($meeting->start_time);
            if ($start === null) {
                continue;
            }

            $end = $this->formatTime($meeting->end_time) ?? $start;
            $format = $this->resolveFormat($meeting);

            $schedule[$weekdayKey][] = [
                'start' => $start,
                'end' => $end,
                'format' => $format->value,
                'format_description' => $format->description(),
                'format_badge_class' => $format->badgeClass(),
            ];
        }

        foreach ($this->weekdays() as $weekday) {
            $unique = collect($schedule[$weekday])
                ->unique(fn (array $entry): string => implode('|', [
                    (string) data_get($entry, 'start'),
                    (string) data_get($entry, 'end'),
                    (string) data_get($entry, 'format'),
                ]))
                ->sortBy(fn (array $entry): string => (string) data_get($entry, 'start'))
                ->values()
                ->all();

            $schedule[$weekday] = $unique;
        }

        return [
            'meeting_id' => $meetingId,
            'city' => $city ?: 'Nao informado',
            'group_name' => $curatedName,
            'link_url' => $linkUrl ?: '#',
            'schedule' => $schedule,
        ];
    }

    private function resolveMeetingId(Collection $matches, ?string $meetingIdHint): string
    {
        if ($meetingIdHint !== null && $matches->contains(fn (VirtualMeeting $meeting): bool => (string) $meeting->meeting_id === $meetingIdHint)) {
            return $meetingIdHint;
        }

        $resolved = $this->resolveMostFrequentText(
            $matches
                ->pluck('meeting_id')
                ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
                ->map(fn ($value): string => trim((string) $value))
        );

        $fallbackFromMatches = trim((string) data_get($matches->first(), 'meeting_id', ''));

        return $resolved ?: ($fallbackFromMatches !== '' ? $fallbackFromMatches : ($meetingIdHint ?? '-'));
    }

    private function resolveMostFrequentText(Collection $values): ?string
    {
        if ($values->isEmpty()) {
            return null;
        }

        /** @var array<string,int> $counts */
        $counts = [];
        foreach ($values as $value) {
            $key = (string) $value;
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        arsort($counts);
        $top = array_key_first($counts);

        return is_string($top) ? $top : null;
    }

    private function resolveFormat(VirtualMeeting $meeting): CuratedMeetingFormat
    {
        $typeLabel = $this->normalizeText((string) ($meeting->type_label ?? ''));
        $formats = collect(is_array($meeting->format_labels) ? $meeting->format_labels : [])
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn ($value): string => $this->normalizeText((string) $value))
            ->values();

        if ((bool) $meeting->is_study || str_contains($typeLabel, 'estudo') || $formats->contains(fn (string $value): bool => str_contains($value, 'estudo'))) {
            return CuratedMeetingFormat::ESTUDO;
        }

        if ((bool) $meeting->is_open || str_contains($typeLabel, 'aberta') || str_contains($typeLabel, 'aberto') || $formats->contains(fn (string $value): bool => str_contains($value, 'aberta') || str_contains($value, 'aberto'))) {
            return CuratedMeetingFormat::ABERTA;
        }

        return CuratedMeetingFormat::FECHADA;
    }

    private function formatTime(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $parts = explode(':', trim($value));
        if (count($parts) < 2) {
            return null;
        }

        return sprintf('%02d:%02d', (int) $parts[0], (int) $parts[1]);
    }

    private function normalizeWeekday(string $value): ?string
    {
        $normalized = $this->normalizeText($value);

        return match ($normalized) {
            'segunda', 'segunda feira', 'segunda-feira' => 'segunda',
            'terca', 'terca feira', 'terca-feira' => 'terca',
            'quarta', 'quarta feira', 'quarta-feira' => 'quarta',
            'quinta', 'quinta feira', 'quinta-feira' => 'quinta',
            'sexta', 'sexta feira', 'sexta-feira' => 'sexta',
            'sabado', 'sabado feira', 'sabado-feira' => 'sabado',
            'domingo' => 'domingo',
            default => null,
        };
    }

    private function isNameCompatible(string $databaseName, string $curatedName): bool
    {
        $left = $this->normalizeNameForMatching($databaseName);
        $right = $this->normalizeNameForMatching($curatedName);

        if ($left === '' || $right === '') {
            return false;
        }

        if ($left === $right || str_contains($left, $right) || str_contains($right, $left)) {
            return true;
        }

        $leftTokens = $this->tokens($left);
        $rightTokens = $this->tokens($right);

        if ($leftTokens === [] || $rightTokens === []) {
            return false;
        }

        $intersection = array_values(array_intersect($leftTokens, $rightTokens));
        $ratio = count($intersection) / max(1, min(count($leftTokens), count($rightTokens)));

        return $ratio >= 0.6;
    }

    private function normalizeNameForMatching(string $value): string
    {
        $normalized = $this->normalizeText($value);

        foreach ([
            '1a' => 'primeira',
            '1o' => 'primeiro',
            '2a' => 'segunda',
            '2o' => 'segundo',
            '3a' => 'terceira',
            '3o' => 'terceiro',
            '4a' => 'quarta',
            '4o' => 'quarto',
            '5a' => 'quinta',
            '5o' => 'quinto',
        ] as $pattern => $replacement) {
            $normalized = preg_replace('/\b'.$pattern.'\b/u', $replacement, (string) $normalized);
        }

        $normalized = preg_replace('/\b(grupo|virtual|online|on line|on-line|de|da|do|dos|das|narcoticos|anonimos|na)\b/u', ' ', (string) $normalized);
        $normalized = preg_replace('/\s+/', ' ', trim((string) $normalized));

        return is_string($normalized) ? $normalized : '';
    }

    /**
     * @return list<string>
     */
    private function tokens(string $value): array
    {
        return collect(explode(' ', $value))
            ->map(fn ($token): string => trim($token))
            ->filter(fn ($token): bool => $token !== '')
            ->values()
            ->all();
    }

    private function normalizeText(string $value): string
    {
        $ascii = Str::ascii($value);
        $lower = mb_strtolower($ascii);
        $clean = preg_replace('/[^a-z0-9\s-]+/i', ' ', $lower);
        $collapsed = preg_replace('/\s+/', ' ', trim((string) $clean));

        return is_string($collapsed) ? $collapsed : '';
    }
}
