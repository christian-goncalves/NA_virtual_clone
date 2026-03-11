<?php

use App\Http\Controllers\VirtualMeetingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/reunioes-virtuais', [VirtualMeetingController::class, 'index'])
    ->name('virtual-meetings.index');