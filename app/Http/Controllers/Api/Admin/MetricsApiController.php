<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\NaVirtualMeetingMetricsService;
use Illuminate\Http\JsonResponse;

class MetricsApiController extends Controller
{
    public function index(NaVirtualMeetingMetricsService $metricsService): JsonResponse
    {
        return response()->json($metricsService->buildDashboardData());
    }
}
