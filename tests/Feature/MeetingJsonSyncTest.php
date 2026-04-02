<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CuratedMeetingJsonSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MeetingJsonSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_json_requires_authentication(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);

        $this->post('/admin/metricas/reunioes/sync-json')->assertStatus(401);
    }

    public function test_sync_json_blocks_non_admin_user(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);
        $user = User::factory()->create(['email' => 'viewer@example.com']);

        $this->actingAs($user)
            ->post('/admin/metricas/reunioes/sync-json')
            ->assertStatus(403);
    }

    public function test_sync_json_redirects_with_success_for_admin(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);

        $this->mock(CuratedMeetingJsonSyncService::class, function ($mock): void {
            $mock->shouldReceive('syncFromSheetToJson')->once()->andReturn([
                'written' => 15,
                'total' => 15,
                'conflicts' => [],
            ]);
        });

        $user = User::factory()->create(['email' => 'admin@example.com']);

        $this->actingAs($user)
            ->post('/admin/metricas/reunioes/sync-json')
            ->assertRedirect('/admin/metricas/reunioes')
            ->assertSessionHas('meeting_json_sync_success');
    }

    public function test_sync_json_redirects_with_error_when_conflicts_exist(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);

        $this->mock(CuratedMeetingJsonSyncService::class, function ($mock): void {
            $mock->shouldReceive('syncFromSheetToJson')->once()->andThrow(ValidationException::withMessages([
                'sheet' => ['Sincronizacao abortada: existem conflitos de resolucao entre planilha e base.'],
            ]));
        });

        $user = User::factory()->create(['email' => 'admin@example.com']);

        $this->actingAs($user)
            ->post('/admin/metricas/reunioes/sync-json')
            ->assertRedirect('/admin/metricas/reunioes')
            ->assertSessionHas('meeting_pdf_error');
    }
}