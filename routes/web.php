<?php

use App\Http\Controllers\VirtualMeetingController;
use Illuminate\Support\Facades\Route;


Route::get('/', [VirtualMeetingController::class, 'index'])
    ->middleware('throttle:web-public')
    ->name('virtual-meetings.index');
