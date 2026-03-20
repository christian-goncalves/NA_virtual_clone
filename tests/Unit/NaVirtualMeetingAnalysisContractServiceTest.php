<?php

namespace Tests\Unit;

use App\Services\NaVirtualMeetingAnalysisContractService;
use Tests\TestCase;

class NaVirtualMeetingAnalysisContractServiceTest extends TestCase
{
    public function test_returns_expected_phase_one_columns_and_filters(): void
    {
        $service = app(NaVirtualMeetingAnalysisContractService::class);

        $definition = $service->getDefinition();

        $this->assertSame([
            'name',
            'meeting_platform',
            'meeting_id',
            'weekday',
            'start_time',
            'end_time',
            'duration_minutes',
            'is_open',
            'is_study',
            'is_lgbt',
            'is_women',
            'is_hybrid',
            'is_active',
        ], $definition['columns']);

        $this->assertSame([
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
        ], $definition['filters']);
    }

    public function test_uses_expected_default_sorting_and_pagination_contract(): void
    {
        $service = app(NaVirtualMeetingAnalysisContractService::class);

        $definition = $service->getDefinition();

        $this->assertSame('weekday', data_get($definition, 'sorting.default_by'));
        $this->assertSame('asc', data_get($definition, 'sorting.default_direction'));
        $this->assertSame(['name', 'weekday', 'start_time', 'meeting_platform', 'is_active'], data_get($definition, 'sorting.allowed'));

        $this->assertSame(20, data_get($definition, 'pagination.default_per_page'));
        $this->assertSame([20, 50, 100], data_get($definition, 'pagination.allowed_per_page'));
    }

    public function test_contains_functional_acceptance_criteria(): void
    {
        $service = app(NaVirtualMeetingAnalysisContractService::class);

        $definition = $service->getDefinition();

        $criteria = data_get($definition, 'acceptance_criteria', []);

        $this->assertNotEmpty($criteria);
        $this->assertCount(6, $criteria);
    }
}
