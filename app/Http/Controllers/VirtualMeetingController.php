<?php

namespace App\Http\Controllers;

use App\Services\NaVirtualMeetingHomepageDataService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

class VirtualMeetingController extends Controller
{
    public function index(NaVirtualMeetingHomepageDataService $service): View
    {
        $cacheKey = (string) config('na_virtual.homepage_cache.key', 'na.virtual.homepage');
        $ttlSeconds = max(1, (int) config('na_virtual.homepage_cache.ttl_seconds', 120));

        $data = Cache::remember($cacheKey, now()->addSeconds($ttlSeconds), function () use ($service): array {
            return $service->buildForHomepage();
        });

        return view('virtual-meetings.index', $data);
    }
}
