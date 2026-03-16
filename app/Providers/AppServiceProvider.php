<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureHttps();
        $this->configureRateLimiting();
    }

    private function configureHttps(): void
    {
        if ((bool) config('app.force_https', false) && ! $this->app->environment('local')) {
            URL::forceScheme('https');
        }
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api-public', function (Request $request): Limit {
            $maxAttempts = max(1, (int) config('na_virtual.rate_limit.api_public_per_minute', 120));

            return Limit::perMinute($maxAttempts)->by($request->ip());
        });

        RateLimiter::for('web-public', function (Request $request): Limit {
            $maxAttempts = max(1, (int) config('na_virtual.rate_limit.web_public_per_minute', 120));

            return Limit::perMinute($maxAttempts)->by($request->ip());
        });
    }
}
