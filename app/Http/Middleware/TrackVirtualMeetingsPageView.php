<?php

namespace App\Http\Middleware;

use App\Services\NaVirtualMeetingMetricsIngestionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackVirtualMeetingsPageView
{
    public function __construct(private readonly NaVirtualMeetingMetricsIngestionService $ingestionService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET')) {
            $this->ingestionService->trackPageView($request, (string) $request->path(), [
                'status_code' => $response->getStatusCode(),
                'route_name' => optional($request->route())->getName(),
            ]);
        }

        return $response;
    }
}
