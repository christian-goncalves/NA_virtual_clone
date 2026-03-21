<?php

namespace Tests\Feature;

use App\Models\MetricPageView;
use App\Models\User;
use App\Models\VirtualMeeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingAnalysisApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_meeting_analysis_api_requires_authentication(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);

        $this->getJson('/api/admin/metricas/reunioes')->assertStatus(401);
    }

    public function test_meeting_analysis_api_blocks_non_admin_user(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);
        $user = User::factory()->create(['email' => 'viewer@example.com']);

        $this->actingAs($user)
            ->getJson('/api/admin/metricas/reunioes')
            ->assertStatus(403);
    }

    public function test_meeting_analysis_api_returns_standardized_payload_for_admin(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);

        $user = User::factory()->create(['email' => 'admin@example.com']);

        VirtualMeeting::query()->create([
            'name' => 'Grupo Delta',
            'meeting_platform' => 'zoom',
            'meeting_id' => '444',
            'weekday' => 'quinta',
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
            'duration_minutes' => 60,
            'is_open' => true,
            'is_study' => false,
            'is_lgbt' => false,
            'is_women' => false,
            'is_hybrid' => false,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->getJson('/api/admin/metricas/reunioes?search_name=Delta')
            ->assertOk()
            ->assertJsonStructure([
                'ok',
                'data',
                'summary' => [
                    'total_filtered',
                    'active_count',
                    'inactive_count',
                    'weekday_distribution',
                    'platform_distribution',
                ],
                'meta' => [
                    'page',
                    'per_page',
                    'total',
                    'last_page',
                    'sort_by',
                    'sort_dir',
                    'performance',
                ],
                'applied_filters',
            ])
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.0.name', 'Grupo Delta');
    }

    public function test_meeting_analysis_api_returns_standardized_validation_error_payload(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);
        $user = User::factory()->create(['email' => 'admin@example.com']);

        $this->actingAs($user)
            ->getJson('/api/admin/metricas/reunioes?time_start=23:00&time_end=10:00')
            ->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonStructure([
                'errors' => [
                    'time_start',
                ],
            ]);
    }

    public function test_meeting_analysis_api_applies_pagination_and_page_selection(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);
        $user = User::factory()->create(['email' => 'admin@example.com']);

        foreach (range(1, 3) as $i) {
            VirtualMeeting::query()->create([
                'name' => 'Grupo Pag '.$i,
                'meeting_platform' => 'zoom',
                'meeting_id' => (string) $i,
                'weekday' => 'sexta',
                'start_time' => sprintf('0%d:00:00', $i),
                'end_time' => sprintf('0%d:30:00', $i),
                'duration_minutes' => 30,
                'is_open' => true,
                'is_study' => false,
                'is_lgbt' => false,
                'is_women' => false,
                'is_hybrid' => false,
                'is_active' => true,
            ]);
        }

        $this->actingAs($user)
            ->getJson('/api/admin/metricas/reunioes?per_page=20&page=1')
            ->assertOk()
            ->assertJsonPath('meta.page', 1)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.per_page', 20);
    }

    public function test_meeting_analysis_api_keeps_filters_in_applied_filters_payload(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);
        $user = User::factory()->create(['email' => 'admin@example.com']);

        VirtualMeeting::query()->create([
            'name' => 'Grupo Preserve',
            'meeting_platform' => 'zoom',
            'meeting_id' => 'p1',
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

        $this->actingAs($user)
            ->getJson('/api/admin/metricas/reunioes?search_name=Preserve&weekday=segunda&sort_by=start_time&sort_dir=desc')
            ->assertOk()
            ->assertJsonPath('applied_filters.search_name', 'Preserve')
            ->assertJsonPath('applied_filters.weekday', 'segunda')
            ->assertJsonPath('applied_filters.sort_by', 'start_time')
            ->assertJsonPath('applied_filters.sort_dir', 'desc');
    }

    public function test_meeting_analysis_api_returns_datatables_contract_when_draw_is_informed(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);
        $user = User::factory()->create(['email' => 'admin@example.com']);

        VirtualMeeting::query()->create([
            'name' => 'Grupo DT',
            'meeting_platform' => 'zoom',
            'meeting_id' => 'dt-1',
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

        $this->actingAs($user)
            ->getJson('/api/admin/metricas/reunioes?draw=3&start=0&length=10&columns[0][data]=name&order[0][column]=0&order[0][dir]=asc')
            ->assertOk()
            ->assertJsonStructure([
                'draw',
                'recordsTotal',
                'recordsFiltered',
                'data',
                'summary',
                'applied_filters',
            ])
            ->assertJsonPath('draw', 3)
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.name', 'Grupo DT');
    }

    public function test_meeting_analysis_api_filters_by_click_block_running(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);
        $user = User::factory()->create(['email' => 'admin@example.com']);
        $runningMeeting = VirtualMeeting::query()->create([
            'name' => 'Grupo Click Running',
            'meeting_platform' => 'zoom',
            'meeting_id' => 'c1',
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
            'name' => 'Grupo Sem Click Running',
            'meeting_platform' => 'zoom',
            'meeting_id' => 'c2',
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
            'occurred_at' => now()->subMinutes(10),
            'route' => '/',
            'event_type' => 'category_click',
            'category' => 'running',
            'session_hash' => 's1',
            'ip_hash' => 'i1',
            'user_agent' => 'phpunit',
            'context' => [
                'meeting_row_id' => $runningMeeting->id,
                'meeting_signature' => 'c1|segunda|08:00',
                'source_section' => 'running',
            ],
        ]);

        $this->actingAs($user)
            ->getJson('/api/admin/metricas/reunioes?click_block=running&click_window=24h')
            ->assertOk()
            ->assertJsonPath('summary.total_filtered', 1)
            ->assertJsonPath('data.0.name', 'Grupo Click Running')
            ->assertJsonPath('data.0.clicks_running', 1)
            ->assertJsonPath('data.0.click_bucket', 'andamento');
    }

    public function test_meeting_analysis_csv_export_returns_filtered_content_for_admin(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);
        $user = User::factory()->create(['email' => 'admin@example.com']);

        VirtualMeeting::query()->create([
            'name' => 'Grupo Export Alpha',
            'meeting_platform' => 'zoom',
            'meeting_id' => 'ex-1',
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
            'name' => 'Grupo Export Beta',
            'meeting_platform' => 'meet',
            'meeting_id' => 'ex-2',
            'weekday' => 'terca',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'duration_minutes' => 60,
            'is_open' => true,
            'is_study' => true,
            'is_lgbt' => false,
            'is_women' => true,
            'is_hybrid' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->get('/api/admin/metricas/reunioes/export.csv?search_name=Export%20Alpha&click_window=24h');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="meeting-analysis.csv"');

        $csv = (string) $response->getContent();

        $this->assertStringContainsString('name,meeting_platform,meeting_id,weekday,start_time,end_time,duration_minutes,is_open,is_study,is_lgbt,is_women,is_hybrid,is_active,clicks_total,clicks_running,clicks_starting_soon,clicks_upcoming', $csv);
        $this->assertStringContainsString('"Grupo Export Alpha",zoom,ex-1,segunda,08:00,09:00,60,Sim,Nao,Nao,Nao,Nao,Sim,0,0,0,0', $csv);
        $this->assertStringNotContainsString('Grupo Export Beta', $csv);
    }

    public function test_meeting_analysis_csv_export_returns_standardized_validation_error_payload(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);
        $user = User::factory()->create(['email' => 'admin@example.com']);

        $this->actingAs($user)
            ->getJson('/api/admin/metricas/reunioes/export.csv?click_window=custom')
            ->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonStructure([
                'errors' => [
                    'click_window',
                ],
            ]);
    }
    public function test_meeting_analysis_api_filters_by_click_block_accessed(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);
        $user = User::factory()->create(['email' => 'admin@example.com']);
        $accessedMeeting = VirtualMeeting::query()->create([
            'name' => 'Grupo Click Accessed',
            'meeting_platform' => 'zoom',
            'meeting_id' => 'ca1',
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
            'name' => 'Grupo Sem Click Accessed',
            'meeting_platform' => 'zoom',
            'meeting_id' => 'ca2',
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
            'occurred_at' => now()->subMinutes(20),
            'route' => '/',
            'event_type' => 'category_click',
            'category' => 'starting_soon',
            'session_hash' => 'sa1',
            'ip_hash' => 'ia1',
            'user_agent' => 'phpunit',
            'context' => [
                'meeting_row_id' => $accessedMeeting->id,
                'meeting_signature' => 'ca1|segunda|08:00',
                'source_section' => 'starting_soon',
            ],
        ]);

        $this->actingAs($user)
            ->getJson('/api/admin/metricas/reunioes?click_block=accessed&click_window=24h')
            ->assertOk()
            ->assertJsonPath('summary.total_filtered', 1)
            ->assertJsonPath('data.0.name', 'Grupo Click Accessed')
            ->assertJsonPath('data.0.click_bucket', 'em_breve');
    }
    public function test_meeting_analysis_api_filters_clicks_by_custom_hour_range(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);
        $user = User::factory()->create(['email' => 'admin@example.com']);
        $rangeInsideMeeting = VirtualMeeting::query()->create([
            'name' => 'Grupo Range Dentro',
            'meeting_platform' => 'zoom',
            'meeting_id' => 'rh1',
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
        $rangeOutsideMeeting = VirtualMeeting::query()->create([
            'name' => 'Grupo Range Fora',
            'meeting_platform' => 'zoom',
            'meeting_id' => 'rh2',
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
            'occurred_at' => now()->subHours(2),
            'route' => '/',
            'event_type' => 'category_click',
            'category' => 'running',
            'session_hash' => 'rh-in',
            'ip_hash' => 'rh-in',
            'user_agent' => 'phpunit',
            'context' => ['meeting_row_id' => $rangeInsideMeeting->id, 'meeting_signature' => 'rh1|segunda|08:00'],
        ]);

        MetricPageView::query()->create([
            'occurred_at' => now()->subHours(20),
            'route' => '/',
            'event_type' => 'category_click',
            'category' => 'upcoming',
            'session_hash' => 'rh-out',
            'ip_hash' => 'rh-out',
            'user_agent' => 'phpunit',
            'context' => ['meeting_row_id' => $rangeOutsideMeeting->id, 'meeting_signature' => 'rh2|terca|10:00'],
        ]);

        $fromHour = now()->subHours(4)->format('Y-m-d H');
        $toHour = now()->subHour()->format('Y-m-d H');

        $this->actingAs($user)
            ->getJson('/api/admin/metricas/reunioes?click_window=custom&click_from_hour='.$fromHour.'&click_to_hour='.$toHour.'&click_block=accessed')
            ->assertOk()
            ->assertJsonPath('summary.total_filtered', 1)
            ->assertJsonPath('data.0.name', 'Grupo Range Dentro');
    }

    public function test_meeting_analysis_api_filters_clicks_by_custom_minute_range_in_datatables_flow(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);
        $user = User::factory()->create(['email' => 'admin@example.com']);
        $minuteInsideMeeting = VirtualMeeting::query()->create([
            'name' => 'Grupo Minuto Dentro',
            'meeting_platform' => 'zoom',
            'meeting_id' => 'rm1',
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
        $minuteOutsideMeeting = VirtualMeeting::query()->create([
            'name' => 'Grupo Minuto Fora',
            'meeting_platform' => 'zoom',
            'meeting_id' => 'rm2',
            'weekday' => 'terca',
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

        $from = now()->subHours(4)->addMinutes(24);
        $to = now()->subHours(1)->addMinutes(24);

        MetricPageView::query()->create([
            'occurred_at' => $from->copy()->addMinutes(5),
            'route' => '/',
            'event_type' => 'category_click',
            'category' => 'running',
            'session_hash' => 'rm-in',
            'ip_hash' => 'rm-in',
            'user_agent' => 'phpunit',
            'context' => ['meeting_row_id' => $minuteInsideMeeting->id, 'meeting_signature' => 'rm1|segunda|08:00'],
        ]);

        MetricPageView::query()->create([
            'occurred_at' => $from->copy()->subMinutes(5),
            'route' => '/',
            'event_type' => 'category_click',
            'category' => 'running',
            'session_hash' => 'rm-out',
            'ip_hash' => 'rm-out',
            'user_agent' => 'phpunit',
            'context' => ['meeting_row_id' => $minuteOutsideMeeting->id, 'meeting_signature' => 'rm2|terca|10:00'],
        ]);

        $fromHour = $from->format('Y-m-d H:i');
        $toHour = $to->format('Y-m-d H:i');

        $this->actingAs($user)
            ->getJson('/api/admin/metricas/reunioes?draw=2&start=0&length=10&columns[0][data]=name&order[0][column]=0&order[0][dir]=asc&click_window=custom&click_from_hour='.$fromHour.'&click_to_hour='.$toHour.'&click_block=accessed')
            ->assertOk()
            ->assertJsonPath('draw', 2)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.name', 'Grupo Minuto Dentro');
    }
    public function test_meeting_analysis_api_forces_accessed_when_custom_window_comes_without_click_block(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);
        $user = User::factory()->create(['email' => 'admin@example.com']);
        $forcedMeeting = VirtualMeeting::query()->create([
            'name' => 'Grupo Forced Accessed',
            'meeting_platform' => 'zoom',
            'meeting_id' => 'fa1',
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
            'name' => 'Grupo Sem Clique Fora',
            'meeting_platform' => 'zoom',
            'meeting_id' => 'fa2',
            'weekday' => 'terca',
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

        $fromHour = now()->subHours(2)->format('Y-m-d H');
        $toHour = now()->subHour()->format('Y-m-d H');

        MetricPageView::query()->create([
            'occurred_at' => now()->subMinutes(70),
            'route' => '/',
            'event_type' => 'category_click',
            'category' => 'running',
            'session_hash' => 'fa-in',
            'ip_hash' => 'fa-in',
            'user_agent' => 'phpunit',
            'context' => ['meeting_row_id' => $forcedMeeting->id, 'meeting_signature' => 'fa1|segunda|08:00'],
        ]);

        $this->actingAs($user)
            ->getJson('/api/admin/metricas/reunioes?click_window=custom&click_from_hour='.$fromHour.'&click_to_hour='.$toHour)
            ->assertOk()
            ->assertJsonPath('summary.total_filtered', 1)
            ->assertJsonPath('applied_filters.click_block', 'accessed')
            ->assertJsonPath('data.0.name', 'Grupo Forced Accessed');
    }
    public function test_meeting_analysis_api_matches_clicks_by_meeting_row_id_without_signature(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);
        $user = User::factory()->create(['email' => 'admin@example.com']);

        $meeting = VirtualMeeting::query()->create([
            'name' => 'Grupo Row Id',
            'meeting_platform' => 'zoom',
            'meeting_id' => 'rid-1',
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
            'meeting_id' => 'rid-2',
            'weekday' => 'terca',
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
            'occurred_at' => now()->subMinutes(30),
            'route' => '/',
            'event_type' => 'category_click',
            'category' => 'running',
            'session_hash' => 'rid-1',
            'ip_hash' => 'rid-1',
            'user_agent' => 'phpunit',
            'context' => [
                'meeting_name' => 'Grupo Row Id',
                'meeting_row_id' => $meeting->id,
                'source_section' => 'running',
            ],
        ]);

        $fromHour = now()->subHours(2)->format('Y-m-d H');
        $toHour = now()->format('Y-m-d H');

        $this->actingAs($user)
            ->getJson('/api/admin/metricas/reunioes?click_window=custom&click_from_hour='.$fromHour.'&click_to_hour='.$toHour.'&click_block=accessed')
            ->assertOk()
            ->assertJsonPath('summary.total_filtered', 1)
            ->assertJsonPath('data.0.name', 'Grupo Row Id');
    }
    public function test_meeting_analysis_api_rejects_custom_hour_range_when_start_is_greater_than_end(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);
        $user = User::factory()->create(['email' => 'admin@example.com']);

        $fromHour = now()->format('Y-m-d H');
        $toHour = now()->subHour()->format('Y-m-d H');

        $this->actingAs($user)
            ->getJson('/api/admin/metricas/reunioes?click_window=custom&click_from_hour='.$fromHour.'&click_to_hour='.$toHour)
            ->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure([
                'errors' => [
                    'click_from_hour',
                ],
            ]);
    }
}
