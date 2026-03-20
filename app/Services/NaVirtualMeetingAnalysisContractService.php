<?php

namespace App\Services;

class NaVirtualMeetingAnalysisContractService
{
    /**
     * @return array<string, mixed>
     */
    public function getDefinition(): array
    {
        $config = config('na_virtual.metrics.meeting_analysis', []);

        $defaultPerPage = (int) data_get($config, 'pagination.default_per_page', 20);
        $allowedPerPage = collect(data_get($config, 'pagination.allowed_per_page', ['20', '50', '100']))
            ->map(fn ($value): int => (int) $value)
            ->filter(fn (int $value): bool => in_array($value, [20, 50, 100], true))
            ->unique()
            ->values()
            ->all();

        if ($allowedPerPage === []) {
            $allowedPerPage = [20, 50, 100];
        }

        if (! in_array($defaultPerPage, $allowedPerPage, true)) {
            $defaultPerPage = 20;
        }

        return [
            'enabled' => (bool) data_get($config, 'enabled', true),
            'objective' => (string) data_get($config, 'objective', ''),
            'columns' => array_values(array_unique(array_map('strval', (array) data_get($config, 'columns', [])))),
            'filters' => array_values(array_unique(array_map('strval', (array) data_get($config, 'filters', [])))),
            'sorting' => [
                'default_by' => (string) data_get($config, 'sorting.default_by', 'weekday'),
                'default_direction' => (string) data_get($config, 'sorting.default_direction', 'asc'),
                'allowed' => array_values(array_unique(array_map('strval', (array) data_get($config, 'sorting.allowed', [])))),
            ],
            'pagination' => [
                'default_per_page' => $defaultPerPage,
                'allowed_per_page' => $allowedPerPage,
            ],
            'acceptance_criteria' => array_values(array_filter(array_map('strval', (array) data_get($config, 'acceptance_criteria', [])))),
        ];
    }
}
