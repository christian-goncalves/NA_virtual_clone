<?php

namespace Tests\Unit;

use App\Models\MetricPageView;
use App\Models\VirtualMeeting;
use App\Services\NaVirtualMeetingAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class NaVirtualMeetingAnalysisServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_applies_filters_and_returns_transformed_rows_and_summary(): void
    {
        VirtualMeeting::query()->create([
            'name' => 'Grupo Alpha',
            'meeting_platform' => 'zoom',
            'meeting_id' => '111',
            'weekday' => 'segunda',
            'start_time' => '10:30:00',
            'end_time' => '11:30:00',
            'duration_minutes' => 60,
            'is_open' => true,
            'is_study' => false,
            'is_lgbt' => false,
            'is_women' => false,
            'is_hybrid' => true,
            'is_active' => true,
        ]);

        VirtualMeeting::query()->create([
            'name' => 'Grupo Beta',
            'meeting_platform' => 'meet',
            'meeting_id' => '222',
            'weekday' => 'terca',
            'start_time' => '22:00:00',
            'end_time' => '23:00:00',
            'duration_minutes' => 60,
            'is_open' => false,
            'is_study' => true,
            'is_lgbt' => false,
            'is_women' => true,
            'is_hybrid' => false,
            'is_active' => false,
        ]);

        $service = app(NaVirtualMeetingAnalysisService::class);

        $result = $service->search([
            'search_name' => 'Alpha',
            'weekday' => 'segunda',
            'time_start' => '10:00',
            'time_end' => '11:00',
            'meeting_platform' => 'zoom',
            'is_open' => true,
            'sort_by' => 'start_time',
            'sort_dir' => 'asc',
            'per_page' => 20,
            'page' => 1,
        ]);

        $this->assertCount(1, $result['rows']);
        $this->assertSame('Grupo Alpha', data_get($result, 'rows.0.name'));
        $this->assertSame('10:30', data_get($result, 'rows.0.start_time'));
        $this->assertSame(1, data_get($result, 'summary.total_filtered'));
        $this->assertSame(1, data_get($result, 'summary.active_count'));
        $this->assertSame(0, data_get($result, 'summary.inactive_count'));
        $this->assertSame('start_time', data_get($result, 'meta.sort_by'));
        $this->assertSame('asc', data_get($result, 'meta.sort_dir'));
    }

    public function test_search_throws_validation_exception_when_time_range_is_invalid(): void
    {
        $service = app(NaVirtualMeetingAnalysisService::class);

        $this->expectException(ValidationException::class);

        $service->search([
            'time_start' => '14:00',
            'time_end' => '09:00',
        ]);
    }

    public function test_search_uses_default_sorting_and_pagination_from_contract(): void
    {
        VirtualMeeting::query()->create([
            'name' => 'Grupo Zeta',
            'meeting_platform' => 'zoom',
            'meeting_id' => '333',
            'weekday' => 'quarta',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'duration_minutes' => 60,
            'is_open' => true,
            'is_study' => true,
            'is_lgbt' => false,
            'is_women' => false,
            'is_hybrid' => false,
            'is_active' => true,
        ]);

        $service = app(NaVirtualMeetingAnalysisService::class);

        $result = $service->search();

        $this->assertSame('weekday', data_get($result, 'meta.sort_by'));
        $this->assertSame('asc', data_get($result, 'meta.sort_dir'));
        $this->assertSame(20, data_get($result, 'meta.per_page'));
        $this->assertSame(1, data_get($result, 'meta.total'));
        $this->assertSame([10, 20, 50, 100], data_get($result, 'meta.performance.allowed_per_page'));
    }

    public function test_search_returns_weekday_and_platform_distributions(): void
    {
        VirtualMeeting::query()->create([
            'name' => 'Grupo Dist 1',
            'meeting_platform' => 'zoom',
            'meeting_id' => 'd1',
            'weekday' => 'segunda',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'duration_minutes' => 60,
            'is_open' => true,
            'is_study' => false,
            'is_lgbt' => false,
            'is_women' => false,
            'is_hybrid' => false,
            'is_active' => true,
        ]);

        VirtualMeeting::query()->create([
            'name' => 'Grupo Dist 2',
            'meeting_platform' => 'meet',
            'meeting_id' => 'd2',
            'weekday' => 'segunda',
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'duration_minutes' => 60,
            'is_open' => false,
            'is_study' => true,
            'is_lgbt' => false,
            'is_women' => true,
            'is_hybrid' => false,
            'is_active' => false,
        ]);

        $service = app(NaVirtualMeetingAnalysisService::class);
        $result = $service->search(['weekday' => 'segunda']);

        $this->assertSame(2, data_get($result, 'summary.total_filtered'));
        $this->assertSame(1, data_get($result, 'summary.active_count'));
        $this->assertSame(1, data_get($result, 'summary.inactive_count'));
        $this->assertSame('segunda', data_get($result, 'summary.weekday_distribution.0.label'));
        $this->assertSame(2, data_get($result, 'summary.weekday_distribution.0.total'));
    }

    public function test_search_rejects_invalid_per_page_value(): void
    {
        $service = app(NaVirtualMeetingAnalysisService::class);

        $this->expectException(ValidationException::class);

        $service->search([
            'per_page' => 30,
        ]);
    }

    public function test_search_supports_datatables_input_contract(): void
    {
        VirtualMeeting::query()->create([
            'name' => 'Grupo DataTables',
            'meeting_platform' => 'zoom',
            'meeting_id' => 'dtx',
            'weekday' => 'segunda',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'duration_minutes' => 60,
            'is_open' => true,
            'is_study' => false,
            'is_lgbt' => false,
            'is_women' => false,
            'is_hybrid' => false,
            'is_active' => true,
        ]);

        $service = app(NaVirtualMeetingAnalysisService::class);

        $result = $service->search([
            'draw' => 7,
            'start' => 0,
            'length' => 10,
            'columns' => [
                ['data' => 'name'],
            ],
            'order' => [
                ['column' => 0, 'dir' => 'asc'],
            ],
        ]);

        $this->assertTrue((bool) data_get($result, 'meta.datatables.enabled'));
        $this->assertSame(7, data_get($result, 'meta.datatables.draw'));
        $this->assertSame(1, data_get($result, 'meta.datatables.records_total'));
        $this->assertSame(1, data_get($result, 'meta.datatables.records_filtered'));
        $this->assertSame('Grupo DataTables', data_get($result, 'rows.0.name'));
    }

    public function test_search_filters_meetings_by_click_block(): void
    {
        $clickMeetingOne = VirtualMeeting::query()->create([
            'name' => 'Grupo Click 1',
            'meeting_platform' => 'zoom',
            'meeting_id' => 'ck1',
            'weekday' => 'segunda',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'duration_minutes' => 60,
            'is_open' => true,
            'is_study' => false,
            'is_lgbt' => false,
            'is_women' => false,
            'is_hybrid' => false,
            'is_active' => true,
        ]);

        VirtualMeeting::query()->create([
            'name' => 'Grupo Click 2',
            'meeting_platform' => 'zoom',
            'meeting_id' => 'ck2',
            'weekday' => 'segunda',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'duration_minutes' => 60,
            'is_open' => true,
            'is_study' => false,
            'is_lgbt' => false,
            'is_women' => false,
            'is_hybrid' => false,
            'is_active' => true,
        ]);

        MetricPageView::query()->create([
            'occurred_at' => now()->subMinutes(5),
            'route' => '/',
            'event_type' => 'category_click',
            'category' => 'running',
            'session_hash' => 's1',
            'ip_hash' => 'i1',
            'user_agent' => 'phpunit',
            'context' => [
                'meeting_row_id' => $clickMeetingOne->id,
                'meeting_signature' => 'ck1|segunda|08:00',
            ],
        ]);

        $service = app(NaVirtualMeetingAnalysisService::class);

        $result = $service->search([
            'click_block' => 'running',
            'click_window' => '24h',
        ]);

        $this->assertSame(1, data_get($result, 'summary.total_filtered'));
        $this->assertSame('Grupo Click 1', data_get($result, 'rows.0.name'));
        $this->assertSame(1, data_get($result, 'rows.0.clicks_running'));
    }
    public function test_search_resolves_click_bucket_with_priority_and_sem_clique(): void
    {
        $dominantMeeting = VirtualMeeting::query()->create([
            'name' => 'Grupo Dominante Running',
            'meeting_platform' => 'zoom',
            'meeting_id' => 'dkr1',
            'weekday' => 'segunda',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'duration_minutes' => 60,
            'is_open' => true,
            'is_study' => false,
            'is_lgbt' => false,
            'is_women' => false,
            'is_hybrid' => false,
            'is_active' => true,
        ]);

        VirtualMeeting::query()->create([
            'name' => 'Grupo Sem Clique',
            'meeting_platform' => 'zoom',
            'meeting_id' => 'dkr2',
            'weekday' => 'terca',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'duration_minutes' => 60,
            'is_open' => false,
            'is_study' => true,
            'is_lgbt' => false,
            'is_women' => false,
            'is_hybrid' => false,
            'is_active' => true,
        ]);

        MetricPageView::query()->create([
            'occurred_at' => now()->subMinutes(5),
            'route' => '/',
            'event_type' => 'category_click',
            'category' => 'running',
            'session_hash' => 'rk1',
            'ip_hash' => 'ip1',
            'user_agent' => 'phpunit',
            'context' => ['meeting_name' => 'Grupo Dominante Running', 'meeting_row_id' => $dominantMeeting->id, 'meeting_signature' => 'dkr1|segunda|08:00'],
        ]);

        MetricPageView::query()->create([
            'occurred_at' => now()->subMinutes(4),
            'route' => '/',
            'event_type' => 'category_click',
            'category' => 'starting_soon',
            'session_hash' => 'rk2',
            'ip_hash' => 'ip2',
            'user_agent' => 'phpunit',
            'context' => ['meeting_name' => 'Grupo Dominante Running', 'meeting_row_id' => $dominantMeeting->id, 'meeting_signature' => 'dkr1|segunda|08:00'],
        ]);

        $service = app(NaVirtualMeetingAnalysisService::class);

        $all = $service->search([
            'click_window' => '24h',
            'sort_by' => 'name',
            'sort_dir' => 'asc',
        ]);

        $this->assertSame('andamento', data_get($all, 'rows.0.click_bucket'));
        $this->assertSame('sem_clique', data_get($all, 'rows.1.click_bucket'));

        $filtered = $service->search([
            'click_block' => 'running',
            'click_window' => '24h',
        ]);

        $this->assertSame(1, data_get($filtered, 'summary.total_filtered'));
        $this->assertSame('Grupo Dominante Running', data_get($filtered, 'rows.0.name'));
    }
}
