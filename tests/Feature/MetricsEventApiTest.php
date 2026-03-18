<?php

namespace Tests\Feature;

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
}
