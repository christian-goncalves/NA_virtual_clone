<?php

use App\Http\Middleware\EnsureAdminUser;
use App\Http\Middleware\HardenMetricsAdminAccess;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TrackVirtualMeetingsPageView;
use App\Http\Middleware\TrackVirtualMeetingsRequestMetric;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $trustedProxies = env('TRUSTED_PROXIES');

        if (is_string($trustedProxies) && trim($trustedProxies) !== '') {
            $middleware->trustProxies(at: trim($trustedProxies));
        }

        $middleware->append(SecurityHeaders::class);

        $middleware->alias([
            'track.vm.pageview' => TrackVirtualMeetingsPageView::class,
            'track.vm.request_metric' => TrackVirtualMeetingsRequestMetric::class,
            'harden.metrics.admin' => HardenMetricsAdminAccess::class,
            'is_admin' => EnsureAdminUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash([
            'current_password',
            'password',
            'password_confirmation',
            'token',
            'api_token',
            'authorization',
        ]);
    })->create();
