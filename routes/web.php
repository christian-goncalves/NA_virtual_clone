<?php

use App\Http\Controllers\VirtualMeetingController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/reunioes-virtuais');

Route::get('/reunioes-virtuais', [VirtualMeetingController::class, 'index'])
    ->middleware('throttle:web-public')
    ->name('virtual-meetings.index');
