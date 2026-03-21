<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingAnalysisPresetsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_meeting_analysis_presets_api_requires_authentication(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);

        $this->getJson('/api/admin/metricas/reunioes/presets')->assertStatus(401);
    }

    public function test_meeting_analysis_presets_api_blocks_non_admin_user(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);
        $user = User::factory()->create(['email' => 'viewer@example.com']);

        $this->actingAs($user)
            ->getJson('/api/admin/metricas/reunioes/presets')
            ->assertStatus(403);
    }

    public function test_meeting_analysis_presets_api_saves_and_lists_presets_for_admin(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);
        $user = User::factory()->create(['email' => 'admin@example.com']);

        $storeResponse = $this->actingAs($user)
            ->postJson('/api/admin/metricas/reunioes/presets', [
                'name' => 'Manha running',
                'filters' => [
                    'weekday' => 'segunda',
                    'time_start' => '08:00',
                    'time_end' => '12:00',
                    'click_block' => 'running',
                    'click_window' => '24h',
                    'sort_by' => 'clicks_running',
                    'sort_dir' => 'desc',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.name', 'Manha running');

        $presetId = (int) data_get($storeResponse->json(), 'data.id');
        $this->assertGreaterThan(0, $presetId);

        $this->actingAs($user)
            ->getJson('/api/admin/metricas/reunioes/presets')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.0.name', 'Manha running')
            ->assertJsonPath('data.0.filters.click_block', 'running')
            ->assertJsonPath('data.0.filters.sort_by', 'clicks_running');
    }

    public function test_meeting_analysis_presets_api_validates_payload(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);
        $user = User::factory()->create(['email' => 'admin@example.com']);

        $this->actingAs($user)
            ->postJson('/api/admin/metricas/reunioes/presets', [
                'name' => 'Custom sem datas',
                'filters' => [
                    'click_window' => 'custom',
                ],
            ])
            ->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure([
                'errors' => [
                    'click_window',
                ],
            ]);
    }

    public function test_meeting_analysis_presets_api_deletes_only_owned_preset(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com', 'admin2@example.com']);

        $owner = User::factory()->create(['email' => 'admin@example.com']);
        $other = User::factory()->create(['email' => 'admin2@example.com']);

        $storeResponse = $this->actingAs($owner)
            ->postJson('/api/admin/metricas/reunioes/presets', [
                'name' => 'Preset Owner',
                'filters' => [
                    'click_window' => '24h',
                ],
            ])
            ->assertOk();

        $presetId = (int) data_get($storeResponse->json(), 'data.id');

        $this->actingAs($other)
            ->deleteJson('/api/admin/metricas/reunioes/presets/'.$presetId)
            ->assertStatus(404)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('code', 'NOT_FOUND');

        $this->actingAs($owner)
            ->deleteJson('/api/admin/metricas/reunioes/presets/'.$presetId)
            ->assertOk()
            ->assertJsonPath('ok', true);
    }
}
