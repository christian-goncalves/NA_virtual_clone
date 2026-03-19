<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetricsRequestMetricTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_reunioes_virtuais_tracks_request_metric(): void
    {
        $this->get('/reunioes-virtuais')->assertOk();

        $this->assertDatabaseHas('metric_request_metrics', [
            'route' => 'reunioes-virtuais',
            'http_method' => 'GET',
            'status_code' => 200,
        ]);
    }
}
