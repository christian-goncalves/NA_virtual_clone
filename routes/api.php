<?php

use App\Http\Controllers\Api\Admin\MeetingAnalysisApiController;
use App\Http\Controllers\Api\Admin\MetricsApiController;
use App\Http\Controllers\Api\MetricsEventController;
use App\Http\Controllers\Api\VirtualMeetingApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api-public')->group(function (): void {
    Route::get('/reunioes-virtuais', [VirtualMeetingApiController::class, 'index'])
        ->middleware('track.vm.request_metric')
        ->name('virtual-meetings.api.index');

    Route::get('/server-time', [VirtualMeetingApiController::class, 'serverTime'])
        ->name('virtual-meetings.api.server-time');

    Route::post('/metrics/event', [MetricsEventController::class, 'store'])
        ->name('metrics.events.store');
});

Route::middleware(['auth.basic', 'is_admin', 'harden.metrics.admin'])->group(function (): void {
    Route::get('/admin/metricas', [MetricsApiController::class, 'index'])
        ->name('admin.metrics.api.index');

    Route::get('/admin/metricas/reunioes', [MeetingAnalysisApiController::class, 'index'])
        ->name('admin.metrics.api.meetings.index');
});
