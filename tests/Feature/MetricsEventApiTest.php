<?php

namespace Tests\Feature;

use App\Models\MetricPageView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetricsEventApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_metrics_event_endpoint_persists_click_event(): void
    {
        $response = $this->postJson('/api/metrics/event', [
            'event_type' => 'category_click',
            'category' => 'running',
            'route' => '/reunioes-virtuais',
            'meeting_name' => 'Grupo Teste',
            'source_section' => 'running',
        ]);

        $response->assertStatus(202)
            ->assertJson([
                'ok' => true,
            ]);

        $this->assertDatabaseHas('metric_page_views', [
            'event_type' => 'category_click',
            'category' => 'running',
            'route' => '/reunioes-virtuais',
        ]);
    }

    public function test_metrics_event_endpoint_generates_unique_event_uuid(): void
    {
        $this->postJson('/api/metrics/event', [
            'event_type' => 'category_click',
            'category' => 'running',
            'route' => '/reunioes-virtuais',
            'meeting_name' => 'Grupo Teste 1',
            'source_section' => 'running',
        ])->assertStatus(202);

        $this->postJson('/api/metrics/event', [
            'event_type' => 'category_click',
            'category' => 'starting_soon',
            'route' => '/reunioes-virtuais',
            'meeting_name' => 'Grupo Teste 2',
            'source_section' => 'starting_soon',
        ])->assertStatus(202);

        $uuids = MetricPageView::query()
            ->where('event_type', 'category_click')
            ->pluck('event_uuid')
            ->all();

        $this->assertCount(2, $uuids);
        $this->assertNotNull($uuids[0]);
        $this->assertNotNull($uuids[1]);
        $this->assertNotSame($uuids[0], $uuids[1]);
    }
    public function test_metrics_event_endpoint_persists_meeting_row_id_in_context(): void
    {
        $response = $this->postJson('/api/metrics/event', [
            'event_type' => 'category_click',
            'category' => 'running',
            'route' => '/reunioes-virtuais',
            'meeting_name' => 'Grupo Teste Contexto',
            'meeting_row_id' => 123,
            'source_section' => 'running',
        ]);

        $response->assertStatus(202)
            ->assertJson([
                'ok' => true,
            ]);

        $row = MetricPageView::query()->latest('id')->first();

        $this->assertSame(123, (int) data_get($row?->context, 'meeting_row_id'));
    }
    public function test_metrics_event_endpoint_persists_meeting_analysis_usage_event(): void
    {
        $response = $this->postJson('/api/metrics/event', [
            'event_type' => 'meeting_analysis_usage',
            'category' => 'apply_filters',
            'route' => '/admin/metricas',
            'meeting_name' => 'Preset Teste',
            'source_section' => 'admin_meeting_analysis',
        ]);

        $response->assertStatus(202)
            ->assertJson([
                'ok' => true,
            ]);

        $this->assertDatabaseHas('metric_page_views', [
            'event_type' => 'meeting_analysis_usage',
            'category' => 'apply_filters',
            'route' => '/admin/metricas',
        ]);
    }
}
