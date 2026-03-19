<?php

namespace App\Http\Middleware;

use App\Services\NaVirtualMeetingMetricsIngestionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackVirtualMeetingsRequestMetric
{
    public function __construct(private readonly NaVirtualMeetingMetricsIngestionService $ingestionService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);
        $response = $next($request);

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        $this->ingestionService->trackRequestMetric($request, (string) $request->path(), [
            'duration_ms' => max(0, $durationMs),
            'status_code' => $response->getStatusCode(),
        ]);

        return $response;
    }
}