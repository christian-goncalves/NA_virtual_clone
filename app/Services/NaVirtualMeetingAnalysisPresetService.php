<?php

namespace App\Services;

use App\Models\MetricMeetingAnalysisPreset;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class NaVirtualMeetingAnalysisPresetService
{
    private const MAX_PRESETS_PER_USER = 15;

    /**
     * @return list<array<string, mixed>>
     */
    public function listForUser(User $user): array
    {
        return MetricMeetingAnalysisPreset::query()
            ->where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (MetricMeetingAnalysisPreset $preset): array => $this->transformPreset($preset))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function saveForUser(User $user, array $payload): array
    {
        $validator = Validator::make($payload, [
            'name' => ['required', 'string', 'max:80'],
            'filters' => ['required', 'array'],
        ]);

        $validated = $validator->validate();
        $name = trim((string) data_get($validated, 'name'));

        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => ['Nome do preset e obrigatorio.'],
            ]);
        }

        $filters = $this->sanitizeAndValidateFilters((array) data_get($validated, 'filters', []));

        $existingPreset = MetricMeetingAnalysisPreset::query()
            ->where('user_id', $user->id)
            ->where('name', $name)
            ->first();

        if ($existingPreset === null) {
            $count = MetricMeetingAnalysisPreset::query()
                ->where('user_id', $user->id)
                ->count();

            if ($count >= self::MAX_PRESETS_PER_USER) {
                throw ValidationException::withMessages([
                    'name' => ['Limite de presets atingido para este usuario.'],
                ]);
            }
        }

        $preset = MetricMeetingAnalysisPreset::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'name' => $name,
            ],
            [
                'filters' => $filters,
            ],
        );

        return $this->transformPreset($preset);
    }

    public function deleteForUser(User $user, int $presetId): bool
    {
        return (bool) MetricMeetingAnalysisPreset::query()
            ->where('id', $presetId)
            ->where('user_id', $user->id)
            ->delete();
    }

    /**
     * @param  array<string, mixed>  $rawFilters
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function sanitizeAndValidateFilters(array $rawFilters): array
    {
        $analysisService = app(NaVirtualMeetingAnalysisService::class);
        $contract = app(NaVirtualMeetingAnalysisContractService::class)->getDefinition();
        $filtersData = $analysisService->resolveFilters($rawFilters, $contract);

        $normalized = $filtersData->toArray();

        return Arr::only($normalized, [
            'search_name',
            'weekday',
            'time_start',
            'time_end',
            'meeting_platform',
            'is_open',
            'is_study',
            'is_lgbt',
            'is_women',
            'is_hybrid',
            'is_active',
            'click_block',
            'click_window',
            'click_from',
            'click_to',
            'sort_by',
            'sort_dir',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformPreset(MetricMeetingAnalysisPreset $preset): array
    {
        return [
            'id' => (int) $preset->id,
            'name' => (string) $preset->name,
            'filters' => (array) ($preset->filters ?? []),
            'updated_at' => optional($preset->updated_at)?->toIso8601String(),
            'created_at' => optional($preset->created_at)?->toIso8601String(),
        ];
    }
}
