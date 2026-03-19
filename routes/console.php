<?php

use App\Services\NaVirtualMeetingMetricsIngestionService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('na:sync-virtual-meetings')
    ->name('sync-na-virtual-meetings-command')
    ->everyThirtyMinutes();

Schedule::call(function (NaVirtualMeetingMetricsIngestionService $ingestionService): void {
    $ingestionService->captureMeetingSnapshot();
})
    ->name('capture-na-virtual-metrics-snapshot')
    ->everyFiveMinutes();

Schedule::call(function (NaVirtualMeetingMetricsIngestionService $ingestionService): void {
    $ingestionService->consolidateHourlyAggregates();
})
    ->name('consolidate-na-virtual-hourly-metrics')
    ->everyTenMinutes();
