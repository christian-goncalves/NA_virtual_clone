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

    public function test_admin_dashboard_allows_ip_from_cidr_allowlist(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);
        config()->set('na_virtual.metrics.admin.ip_allowlist', ['10.0.0.0/8']);

        $user = User::factory()->create(['email' => 'admin@example.com']);

        $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '10.1.2.3'])
            ->get('/admin/metricas')
            ->assertOk();
    }

    public function test_admin_dashboard_blocks_ip_outside_cidr_allowlist(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);
        config()->set('na_virtual.metrics.admin.ip_allowlist', ['10.0.0.0/8']);

        $user = User::factory()->create(['email' => 'admin@example.com']);

        $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '192.168.1.20'])
            ->get('/admin/metricas')
            ->assertStatus(403);
    }


    public function test_admin_meeting_analysis_page_blocks_ip_outside_allowlist(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);
        config()->set('na_virtual.metrics.admin.ip_allowlist', ['10.0.0.0/8']);

        $user = User::factory()->create(['email' => 'admin@example.com']);

        $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '192.168.1.20'])
            ->get('/admin/metricas/reunioes')
            ->assertStatus(403);
    }
    public function test_admin_meeting_analysis_api_blocks_ip_outside_allowlist(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);
        config()->set('na_virtual.metrics.admin.ip_allowlist', ['10.0.0.0/8']);

        $user = User::factory()->create(['email' => 'admin@example.com']);

        $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '192.168.1.20'])
            ->getJson('/api/admin/metricas/reunioes')
            ->assertStatus(403);
    }

    public function test_admin_meeting_analysis_presets_api_blocks_ip_outside_allowlist(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);
        config()->set('na_virtual.metrics.admin.ip_allowlist', ['10.0.0.0/8']);

        $user = User::factory()->create(['email' => 'admin@example.com']);

        $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '192.168.1.20'])
            ->getJson('/api/admin/metricas/reunioes/presets')
            ->assertStatus(403);
    }
}

