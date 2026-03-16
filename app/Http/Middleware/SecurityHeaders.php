<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! (bool) config('app.security_headers_enabled', true)) {
            return $response;
        }

        $isLocalEnvironment = app()->environment(['local', 'testing']);
        $enforceCspInLocal = (bool) config('app.security_headers_enforce_in_local', false);

        if (! $isLocalEnvironment || $enforceCspInLocal) {
            $viteDevHosts = [
                'http://localhost:5173',
                'http://127.0.0.1:5173',
                'http://[::1]:5173',
                'ws://localhost:5173',
                'ws://127.0.0.1:5173',
                'ws://[::1]:5173',
            ];
            $viteHttpSources = implode(' ', array_filter($viteDevHosts, static fn (string $host): bool => str_starts_with($host, 'http')));
            $viteConnectSources = implode(' ', $viteDevHosts);

            $scriptSrc = "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com";
            $styleSrc = "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com";
            $connectSrc = "connect-src 'self' https:";

            if ($isLocalEnvironment) {
                $scriptSrc .= ' '.$viteHttpSources;
                $styleSrc .= ' '.$viteHttpSources;
                $connectSrc .= ' '.$viteConnectSources;
            }

            $csp = implode('; ', [
                "default-src 'self'",
                $scriptSrc,
                $styleSrc,
                "img-src 'self' data: https:",
                "font-src 'self' data: https://cdnjs.cloudflare.com",
                $connectSrc,
                "frame-ancestors 'none'",
                "base-uri 'self'",
                "object-src 'none'",
            ]);

            $response->headers->set('Content-Security-Policy', $csp);
        }

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
