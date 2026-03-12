<?php

use App\Http\Controllers\Api\VirtualMeetingApiController;
use Illuminate\Support\Facades\Route;

Route::get('/reunioes-virtuais', [VirtualMeetingApiController::class, 'index'])
    ->name('virtual-meetings.api.index');

