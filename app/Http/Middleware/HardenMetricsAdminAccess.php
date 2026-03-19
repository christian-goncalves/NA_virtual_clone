<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HardenMetricsAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldRequireHttps($request) && ! $request->isSecure()) {
            abort(403);
        }

        if (! $this->ipAllowed($request->ip())) {
            abort(403);
        }

        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }

    private function ipAllowed(?string $ip): bool
    {
        $allowlist = collect(config('na_virtual.metrics.admin.ip_allowlist', []))
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => trim($value))
            ->values();

        if ($allowlist->isEmpty()) {
            return true;
        }

        if ($ip === null || trim($ip) === '') {
            return false;
        }

        return $allowlist->contains(trim($ip));
    }

    private function shouldRequireHttps(Request $request): bool
    {
        if (! app()->environment('production')) {
            return false;
        }

        return (bool) config('na_virtual.metrics.admin.require_https', true);
    }
}
