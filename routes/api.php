<?php

use App\Http\Controllers\Api\MetricsEventController;
use App\Http\Controllers\Api\VirtualMeetingApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api-public')->group(function (): void {
    Route::get('/reunioes-virtuais', [VirtualMeetingApiController::class, 'index'])
        ->middleware('track.vm.request_metric')
        ->name('virtual-meetings.api.index');

    Route::post('/metrics/event', [MetricsEventController::class, 'store'])
        ->name('metrics.events.store');
});
