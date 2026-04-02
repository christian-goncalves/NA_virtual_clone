<?php

namespace Tests\Feature;

use App\Models\MetricMeetingSnapshot;
use App\Models\User;
use App\Models\VirtualMeeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MetricsAdminApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_metrics_api_requires_authentication(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);

        $this->getJson('/api/admin/metricas')->assertStatus(401);
    }

    public function test_admin_metrics_api_blocks_non_admin_user(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);
        $user = User::factory()->create(['email' => 'viewer@example.com']);

        $this->actingAs($user)
            ->getJson('/api/admin/metricas')
            ->assertStatus(403);
    }

    public function test_admin_metrics_api_falls_back_to_live_running_when_snapshot_is_missing(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);

        $user = User::factory()->create(['email' => 'admin@example.com']);

        $start = now()->subMinutes(30)->format('H:i:s');
        $end = now()->addMinutes(30)->format('H:i:s');

        VirtualMeeting::query()->create([
            'name' => 'Grupo Fallback',
            'start_time' => $start,
            'end_time' => $end,
            'timezone' => 'America/Sao_Paulo',
            'is_active' => true,
        ]);

        Cache::forget('na.virtual.metrics.dashboard');

        $this->actingAs($user)
            ->getJson('/api/admin/metricas')
            ->assertOk()
            ->assertJsonPath('runningNow', 1);
    }

    public function test_admin_metrics_api_prefers_live_running_even_with_snapshot_value(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);

        $user = User::factory()->create(['email' => 'admin@example.com']);

        MetricMeetingSnapshot::query()->create([
            'measured_at' => now(),
            'in_progress_count' => 99,
            'within_1h_count' => 99,
            'within_6h_count' => 99,
        ]);

        $start = now()->subMinutes(30)->format('H:i:s');
        $end = now()->addMinutes(30)->format('H:i:s');

        VirtualMeeting::query()->create([
            'name' => 'Grupo Live Prioritario',
            'start_time' => $start,
            'end_time' => $end,
            'timezone' => 'America/Sao_Paulo',
            'is_active' => true,
        ]);

        Cache::forget('na.virtual.metrics.dashboard');

        $this->actingAs($user)
            ->getJson('/api/admin/metricas')
            ->assertOk()
            ->assertJsonPath('runningNow', 1);
    }

    public function test_admin_metrics_api_returns_dashboard_payload_for_admin(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);
        $user = User::factory()->create(['email' => 'admin@example.com']);

        $this->actingAs($user)
            ->getJson('/api/admin/metricas')
            ->assertOk()
            ->assertJsonStructure([
                'accessesToday',
                'accessesLastHour',
                'runningNow',
                'syncSuccessRate24h',
                'availabilityByHour',
                'syncStatusByHour',
                'syncStatusTotals24h',
                'latencyByHour',
                'topSlowRoutes',
                'recentSyncRuns',
            ]);
    }
}
