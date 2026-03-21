<?php

namespace Tests\Feature;

use App\Models\MetricMeetingSnapshot;
use App\Models\MetricPageView;
use App\Models\MetricRequestMetric;
use App\Models\MetricSyncRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminMetricsDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_authentication(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);

        $this->get('/admin/metricas')->assertStatus(401);
    }

    public function test_dashboard_blocks_non_admin_user(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);
        $user = User::factory()->create(['email' => 'viewer@example.com']);

        $this->actingAs($user)
            ->get('/admin/metricas')
            ->assertStatus(403);
    }

    public function test_dashboard_renders_kpis_and_link_to_meeting_analysis_for_admin(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);

        $user = User::factory()->create(['email' => 'admin@example.com']);

        MetricPageView::query()->create([
            'occurred_at' => Carbon::now()->subMinutes(10),
            'route' => 'reunioes-virtuais',
            'event_type' => 'page_view',
            'category' => null,
            'session_hash' => 's1',
            'ip_hash' => 'i1',
            'user_agent' => 'phpunit',
            'context' => [],
        ]);

        MetricMeetingSnapshot::query()->create([
            'measured_at' => now(),
            'in_progress_count' => 7,
            'within_1h_count' => 10,
            'within_6h_count' => 30,
        ]);

        MetricSyncRun::query()->create([
            'started_at' => now()->subMinutes(30),
            'finished_at' => now()->subMinutes(29),
            'duration_ms' => 1200,
            'status' => 'success',
            'meetings_found' => 100,
            'meetings_saved' => 80,
            'meetings_updated' => 20,
            'meetings_inactivated' => 0,
            'source_url' => 'https://www.na.org.br/virtual/',
        ]);

        MetricRequestMetric::query()->create([
            'occurred_at' => now()->subMinutes(20),
            'route' => 'reunioes-virtuais',
            'http_method' => 'GET',
            'status_code' => 200,
            'duration_ms' => 150,
            'session_hash' => 's1',
            'ip_hash' => 'i1',
        ]);

        $this->actingAs($user)
            ->get('/admin/metricas')
            ->assertOk()
            ->assertSeeText('Dashboard de Metricas')
            ->assertSeeText('Acessos hoje')
            ->assertSeeText('Disponibilidade por faixa (24h)')
            ->assertSeeText('Sucesso x falha de sync (24h)')
            ->assertSeeText('Latencia media 24h')
            ->assertSeeText('Top rotas lentas')
            ->assertSeeText('Ultimas sincronizacoes')
            ->assertSeeText('Abrir analise de reunioes')
            ->assertSee('href="'.route('admin.metrics.meetings.index').'"', false)
            ->assertDontSee('id="meeting-analysis-datatable"', false);
    }

    public function test_meeting_analysis_page_requires_authentication(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);

        $this->get('/admin/metricas/reunioes')->assertStatus(401);
    }

    public function test_meeting_analysis_page_renders_simple_datatable_for_admin(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);

        $user = User::factory()->create(['email' => 'admin@example.com']);

        $this->actingAs($user)
            ->get('/admin/metricas/reunioes')
            ->assertOk()
            ->assertSeeText('Analise de Reunioes')
            ->assertSee('id="meeting-analysis-datatable"', false)
            ->assertSee('id="meeting-analysis-click-block-buttons"', false)
            ->assertSee('data-click-block="accessed"', false)
            ->assertSee('data-click-block="running"', false)
            ->assertSee('data-click-block="starting_soon"', false)
            ->assertSee('data-click-block="upcoming"', false)
            ->assertDontSee('id="meeting-analysis-presets"', false)
            ->assertDontSee('id="meeting-analysis-guided-suggestions"', false);
    }
}
