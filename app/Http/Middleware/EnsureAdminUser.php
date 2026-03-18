<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $allowedEmails = collect(config('na_virtual.metrics.admin_emails', []))
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => mb_strtolower(trim($value)))
            ->values();

        if ($allowedEmails->isEmpty()) {
            abort(403);
        }

        if (! $allowedEmails->contains(mb_strtolower((string) $user->email))) {
            abort(403);
        }

        return $next($request);
    }
}
