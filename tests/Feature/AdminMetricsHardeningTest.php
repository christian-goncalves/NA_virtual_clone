<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMetricsHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_blocks_non_allowlisted_ip_when_list_is_configured(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);
        config()->set('na_virtual.metrics.admin.ip_allowlist', ['127.0.0.1']);

        $user = User::factory()->create(['email' => 'admin@example.com']);

        $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '10.1.2.3'])
            ->get('/admin/metricas')
            ->assertStatus(403);
    }

    public function test_admin_dashboard_allows_allowlisted_ip_and_sets_no_cache_headers(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);
        config()->set('na_virtual.metrics.admin.ip_allowlist', ['127.0.0.1']);

        $user = User::factory()->create(['email' => 'admin@example.com']);

        $response = $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get('/admin/metricas')
            ->assertOk()
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('Expires', '0');

        $cacheControl = (string) $response->headers->get('Cache-Control', '');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
    }
}
