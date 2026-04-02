<?php

namespace App\Services;

use App\Enums\CuratedMeetingFormat;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CuratedMeetingJsonSourceService
{
    /** @var array<string, mixed> */
    private array $lastSummary = [];

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws ValidationException
     */
    public function loadValidatedGroups(): array
    {
        $path = (string) config('na_virtual.curated_groups.json_path', resource_path('data/curated-meeting-groups.json'));

        if ($path === '' || ! is_file($path)) {
            throw ValidationException::withMessages([
                'json' => ['Arquivo JSON de curadoria nao encontrado.'],
            ]);
        }

        $raw = file_get_contents($path);
        if (! is_string($raw) || trim($raw) === '') {
            throw ValidationException::withMessages([
                'json' => ['Arquivo JSON de curadoria esta vazio.'],
            ]);
        }

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            Log::warning('json_validation_failed', [
                'path' => $path,
                'message' => $exception->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'json' => ['JSON invalido para curadoria de reunioes.'],
            ]);
        }

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'json' => ['JSON de curadoria deve ser uma lista de grupos.'],
            ]);
        }

        $errors = [];
        $groups = [];

        foreach ($decoded as $index => $group) {
            if (! is_array($group)) {
                $errors["groups.{$index}"][] = 'Grupo invalido.';
                continue;
            }

            $groupName = trim((string) data_get($group, 'group_name', ''));
            $linkUrl = trim((string) data_get($group, 'link_url', ''));
            $meetingId = data_get($group, 'meeting_id');
            $meetingId = is_scalar($meetingId) ? trim((string) $meetingId) : '';

            if ($groupName === '') {
                $errors["groups.{$index}.group_name"][] = 'Nome do grupo obrigatorio.';
            }

            if ($linkUrl === '') {
                $errors["groups.{$index}.link_url"][] = 'Link do grupo obrigatorio.';
            }

            $schedule = data_get($group, 'schedule');
            if (! is_array($schedule)) {
                $errors["groups.{$index}.schedule"][] = 'Schedule obrigatorio.';
                continue;
            }

            $normalizedSchedule = [];
            foreach ($this->weekdays() as $weekday) {
                $entries = data_get($schedule, $weekday);

                if (! is_array($entries)) {
                    $errors["groups.{$index}.schedule.{$weekday}"][] = 'Dia da semana deve ser lista de horarios.';
                    $normalizedSchedule[$weekday] = [];
                    continue;
                }

                $normalizedEntries = [];
                foreach ($entries as $entryIndex => $entry) {
                    if (! is_array($entry)) {
                        $errors["groups.{$index}.schedule.{$weekday}.{$entryIndex}"][] = 'Horario invalido.';
                        continue;
                    }

                    $start = trim((string) data_get($entry, 'start', ''));
                    $end = trim((string) data_get($entry, 'end', ''));
                    $formatRaw = trim((string) data_get($entry, 'format', ''));
                    $format = CuratedMeetingFormat::tryFrom($formatRaw);

                    if (! preg_match('/^\d{2}:\d{2}$/', $start)) {
                        $errors["groups.{$index}.schedule.{$weekday}.{$entryIndex}.start"][] = 'Horario inicial invalido (HH:MM).';
                    }

                    if (! preg_match('/^\d{2}:\d{2}$/', $end)) {
                        $errors["groups.{$index}.schedule.{$weekday}.{$entryIndex}.end"][] = 'Horario final invalido (HH:MM).';
                    }

                    if ($format === null) {
                        $errors["groups.{$index}.schedule.{$weekday}.{$entryIndex}.format"][] = 'Formato deve ser F, E ou A.';
                        continue;
                    }

                    $normalizedEntries[] = [
                        'start' => $start,
                        'end' => $end,
                        'format' => $format->value,
                        'format_description' => $format->description(),
                        'format_badge_class' => $format->badgeClass(),
                    ];
                }

                usort($normalizedEntries, fn (array $a, array $b): int => strcmp((string) data_get($a, 'start', ''), (string) data_get($b, 'start', '')));
                $normalizedSchedule[$weekday] = $normalizedEntries;
            }

            $groups[] = [
                'meeting_id' => $meetingId !== '' ? $meetingId : '-',
                'group_name' => $groupName,
                'link_url' => $linkUrl,
                'schedule' => $normalizedSchedule,
            ];
        }

        if ($errors !== []) {
            Log::warning('json_validation_failed', [
                'path' => $path,
                'error_fields' => array_keys($errors),
            ]);

            throw ValidationException::withMessages($errors);
        }

        if ($groups === []) {
            throw ValidationException::withMessages([
                'json' => ['JSON sem grupos validos para exportacao.'],
            ]);
        }

        usort($groups, function (array $a, array $b): int {
            $left = mb_strtolower(Str::ascii((string) data_get($a, 'group_name', '')));
            $right = mb_strtolower(Str::ascii((string) data_get($b, 'group_name', '')));

            return strcmp($left, $right);
        });

        $this->lastSummary = [
            'total_sheet_rows' => count($decoded),
            'total_sheet_valid_pairs' => count($groups),
            'resolved_count' => count($groups),
            'conflicts_count' => 0,
            'conflicts_by_reason' => [
                'id_not_found' => 0,
                'name_mismatch' => 0,
                'ambiguous_match' => 0,
            ],
            'conflicts' => [],
            'generated_at' => now()->toIso8601String(),
            'source' => 'json',
        ];

        return $groups;
    }

    /**
     * @return array<string, mixed>
     */
    public function lastSummary(): array
    {
        return $this->lastSummary;
    }

    /**
     * @return list<string>
     */
    public function weekdays(): array
    {
        return ['segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado', 'domingo'];
    }
}