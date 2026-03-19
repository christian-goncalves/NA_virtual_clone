<?php

use App\Services\NaVirtualMeetingMetricsIngestionService;
use App\Services\NaVirtualMeetingMetricsOperationalAlertService;
use App\Services\NaVirtualMeetingMetricsRetentionService;
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

Schedule::call(function (NaVirtualMeetingMetricsOperationalAlertService $alertService): void {
    $alertService->evaluateHealth();
})
    ->name('evaluate-na-virtual-metrics-health-alerts')
    ->everyFiveMinutes();

Schedule::call(function (NaVirtualMeetingMetricsRetentionService $retentionService): void {
    $retentionService->prune();
})
    ->name('prune-na-virtual-metrics-retention')
    ->dailyAt('03:00');
