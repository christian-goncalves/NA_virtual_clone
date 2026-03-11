<?php

namespace Tests\Feature;

use App\Jobs\SyncNaVirtualMeetingsJob;
use App\Services\NaVirtualMeetingSyncService;
use RuntimeException;
use Tests\TestCase;

class SyncNaVirtualMeetingsJobTest extends TestCase
{
    public function test_job_calls_sync_service_once(): void
    {
        $this->mock(NaVirtualMeetingSyncService::class, function ($mock): void {
            $mock->shouldReceive('sync')
                ->once()
                ->andReturn([
                    'total_found' => 1,
                    'total_created' => 1,
                    'total_updated' => 0,
                    'total_inactivated' => 0,
                    'source_url' => 'https://www.na.org.br/virtual/',
                ]);
        });

        $job = new SyncNaVirtualMeetingsJob();

        $this->app->call([$job, 'handle']);
    }

    public function test_job_rethrows_exception_when_sync_fails(): void
    {
        $this->mock(NaVirtualMeetingSyncService::class, function ($mock): void {
            $mock->shouldReceive('sync')
                ->once()
                ->andThrow(new RuntimeException('sync failed'));
        });

        $job = new SyncNaVirtualMeetingsJob();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('sync failed');

        $this->app->call([$job, 'handle']);
    }
}

