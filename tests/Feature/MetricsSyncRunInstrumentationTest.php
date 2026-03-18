<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetricsSyncRunInstrumentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_sync_creates_metric_sync_run_record(): void
    {
        Http::fake([
            'https://www.na.org.br/wp-admin/admin-ajax.php*' => Http::response('falha', 500),
        ]);

        $exitCode = Artisan::call('na:sync-virtual-meetings');

        $this->assertSame(1, $exitCode);

        $this->assertDatabaseHas('metric_sync_runs', [
            'status' => 'failed',
        ]);
    }
}
