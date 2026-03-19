<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
