<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetricsPageViewTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_reunioes_virtuais_tracks_page_view_metric(): void
    {
        $this->get('/reunioes-virtuais')->assertOk();

        $this->assertDatabaseHas('metric_page_views', [
            'route' => 'reunioes-virtuais',
            'event_type' => 'page_view',
        ]);
    }
}
