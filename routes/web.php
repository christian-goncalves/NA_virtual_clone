<?php

use App\Http\Controllers\Admin\MetricsDashboardController;
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
});
