<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CuratedMeetingJsonSourceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingPdfPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_pdf_requires_authentication(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);

        $this->get('/admin/metricas/reunioes/preview-pdf')->assertStatus(401);
    }

    public function test_preview_pdf_blocks_non_admin_user(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);
        $user = User::factory()->create(['email' => 'viewer@example.com']);

        $this->actingAs($user)
            ->get('/admin/metricas/reunioes/preview-pdf')
            ->assertStatus(403);
    }

    public function test_preview_pdf_renders_weekly_grid_for_admin(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);

        $this->mock(CuratedMeetingJsonSourceService::class, function ($mock): void {
            $mock->shouldReceive('loadValidatedGroups')->once()->andReturn([
                [
                    'meeting_id' => '4430251323',
                    'group_name' => 'Bom dia Brasil',
                    'link_url' => 'https://example.org/bomdia',
                    'schedule' => [
                        'segunda' => [['start' => '09:00', 'end' => '11:00', 'format' => 'A', 'format_badge_class' => 'pdf-badge-type-open']],
                        'terca' => [['start' => '19:00', 'end' => '20:30', 'format' => 'F', 'format_badge_class' => 'pdf-badge-type-closed']],
                        'quarta' => [['start' => '20:00', 'end' => '21:30', 'format' => 'E', 'format_badge_class' => 'pdf-badge-type-study']],
                        'quinta' => [],
                        'sexta' => [],
                        'sabado' => [],
                        'domingo' => [],
                    ],
                ],
            ]);
            $mock->shouldReceive('lastSummary')->once()->andReturn([
                'total_sheet_rows' => 1,
                'total_sheet_valid_pairs' => 1,
                'resolved_count' => 1,
                'conflicts_count' => 0,
            ]);
        });

        $user = User::factory()->create(['email' => 'admin@example.com']);

        $this->actingAs($user)
            ->get('/admin/metricas/reunioes/preview-pdf')
            ->assertOk()
            ->assertSeeText('Voltar para analise')
            ->assertSeeText('GRUPO')
            ->assertSeeText('LINK')
            ->assertSeeText('2ª')
            ->assertSeeText('DOM')
            ->assertSeeText('09:00')
            ->assertSee('pdf-preview-scroll', false)
            ->assertSee('pdf-preview-canvas', false)
            ->assertSee('brand-header', false)
            ->assertSee('link-btn-icon-svg', false)
            ->assertSee('link-btn-icon', false)
            ->assertSee('slot-time', false)
            ->assertSee('slot-marker', false)
            ->assertSee('legend legend-bottom', false)
            ->assertSee('pdf-badge-type-open', false)
            ->assertDontSee('>F<', false)
            ->assertDontSee('>E<', false)
            ->assertDontSee('>A<', false)
            ->assertSee('href="https://example.org/bomdia"', false)
            ->assertDontSee('href="'.route('admin.metrics.meetings.export.pdf').'"', false);
    }
}
