<?php

namespace App\Http\Controllers;

use App\Services\NaVirtualMeetingGroupingService;
use Illuminate\Contracts\View\View;

class VirtualMeetingController extends Controller
{
    public function index(NaVirtualMeetingGroupingService $service): View
    {
        return view('virtual-meetings.index', $service->buildHomePageData());
    }
}