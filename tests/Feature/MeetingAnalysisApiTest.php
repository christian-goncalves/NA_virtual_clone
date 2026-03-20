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

        VirtualMeeting::query()->create([
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
                'meeting_name' => 'Grupo Click Running',
                'source_section' => 'running',
            ],
        ]);

        $this->actingAs($user)
            ->getJson('/api/admin/metricas/reunioes?click_block=running&click_window=24h')
            ->assertOk()
            ->assertJsonPath('summary.total_filtered', 1)
            ->assertJsonPath('data.0.name', 'Grupo Click Running')
            ->assertJsonPath('data.0.clicks_running', 1);
    }
}
