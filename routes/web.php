<?php

use App\Http\Controllers\Admin\MetricsDashboardController;
use App\Http\Controllers\Admin\MeetingJsonSyncController;
use App\Http\Controllers\Admin\MeetingPdfExportController;
use App\Http\Controllers\Admin\MeetingPdfPreviewController;
use App\Http\Controllers\VirtualMeetingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:web-public', 'track.vm.pageview', 'track.vm.request_metric'])->group(function (): void {
    Route::get('/', [VirtualMeetingController::class, 'index'])
        ->name('virtual-meetings.index');

    Route::get('/reunioes-virtuais', [VirtualMeetingController::class, 'index'])
        ->name('virtual-meetings.list');
});

Route::middleware(['auth.basic', 'is_admin', 'harden.metrics.admin'])->group(function (): void {
    Route::get('/admin/metricas', [MetricsDashboardController::class, 'index'])
        ->name('admin.metrics.index');

    Route::get('/admin/metricas/reunioes', [MetricsDashboardController::class, 'meetingAnalysis'])
        ->name('admin.metrics.meetings.index');

    Route::get('/admin/csanovo', MeetingPdfPreviewController::class)
        ->name('admin.metrics.meetings.preview.pdf');

    Route::post('/admin/metricas/reunioes/sync-json', MeetingJsonSyncController::class)
        ->name('admin.metrics.meetings.sync.json');

    Route::get('/admin/metricas/reunioes/export.pdf', MeetingPdfExportController::class)
        ->name('admin.metrics.meetings.export.pdf');
});
