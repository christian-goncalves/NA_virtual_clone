<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\NaVirtualMeetingAnalysisContractService;
use App\Services\NaVirtualMeetingMetricsService;
use Illuminate\Http\Response;

class MetricsDashboardController extends Controller
{
    public function index(
        NaVirtualMeetingMetricsService $metricsService,
        NaVirtualMeetingAnalysisContractService $contractService,
    ): Response {
        $data = $metricsService->buildDashboardData();

        return response()
            ->view('admin.metrics.index', array_merge($data, [
                'meetingAnalysis' => [
                    'rows' => [],
                    'summary' => [
                        'total_filtered' => 0,
                        'active_count' => 0,
                        'inactive_count' => 0,
                    ],
                    'meta' => [
                        'page' => 1,
                        'last_page' => 1,
                    ],
                    'applied_filters' => [],
                    'validation_errors' => [],
                ],
                'meetingAnalysisContract' => $contractService->getDefinition(),
            ]))
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
