<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class NaVirtualMeetingSchedulerTest extends TestCase
{
    public function test_scheduler_lists_sync_metrics_and_retention_tasks(): void
    {
        Artisan::call('schedule:list');
        $output = Artisan::output();

        $this->assertStringContainsString('*/30', $output);
        $this->assertStringContainsString('na:sync-virtual-meetings', $output);
        $this->assertStringContainsString('*/5', $output);
        $this->assertStringContainsString('capture-na-virtual-metrics-snapshot', $output);
        $this->assertStringContainsString('*/10', $output);
        $this->assertStringContainsString('consolidate-na-virtual-hourly-metrics', $output);
        $this->assertStringContainsString('evaluate-na-virtual-metrics-health-alerts', $output);
        $this->assertStringContainsString('prune-na-virtual-metrics-retention', $output);
    }
}
