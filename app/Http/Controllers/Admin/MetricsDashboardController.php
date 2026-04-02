<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\NaVirtualMeetingMetricsService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class MetricsDashboardController extends Controller
{
    public function index(NaVirtualMeetingMetricsService $metricsService): Response
    {
        $data = $metricsService->buildDashboardData();

        return response()
            ->view('admin.metrics.index', $data)
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function meetingAnalysis(): Response
    {
        $summary = Cache::get((string) config('na_virtual.curated_groups.export_summary_cache_key', 'na.virtual.curated_groups.last_export_summary'));

        return response()
            ->view('admin.metrics.meeting-analysis', [
                'curatedExportSummary' => is_array($summary) ? $summary : null,
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
