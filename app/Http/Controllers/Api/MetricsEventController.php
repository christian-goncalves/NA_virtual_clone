<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NaVirtualMeetingMetricsIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetricsEventController extends Controller
{
    public function store(Request $request, NaVirtualMeetingMetricsIngestionService $ingestionService): JsonResponse
    {
        $payload = $request->validate([
            'event_type' => ['nullable', 'string', 'max:40'],
            'category' => ['nullable', 'string', 'max:80'],
            'route' => ['nullable', 'string', 'max:120'],
            'meeting_name' => ['nullable', 'string', 'max:255'],
            'meeting_row_id' => ['nullable', 'integer', 'min:1'],
            'meeting_signature' => ['nullable', 'string', 'max:120'],
            'source_section' => ['nullable', 'string', 'max:80'],
        ]);

        $ingestionService->trackEvent($request, $payload);

        return response()->json([
            'ok' => true,
        ], 202);
    }
}
