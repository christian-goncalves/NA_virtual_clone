<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class NaVirtualMeetingSchedulerTest extends TestCase
{
    public function test_sync_job_is_scheduled_every_thirty_minutes(): void
    {
        Artisan::call('schedule:list');
        $output = Artisan::output();

        $this->assertStringContainsString('*/30 * * * *', $output);
        $this->assertStringContainsString('sync-na-virtual-meetings-job', $output);
    }
}
