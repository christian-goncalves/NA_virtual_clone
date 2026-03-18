<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\NaVirtualMeetingMetricsService;
use Illuminate\Http\Response;

class MetricsDashboardController extends Controller
{
    public function index(NaVirtualMeetingMetricsService $metricsService): Response
    {
        $data = $metricsService->buildDashboardData();

        return response()
            ->view('admin.metrics.index', $data)
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
