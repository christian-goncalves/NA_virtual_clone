<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CuratedMeetingJsonSourceService;
use App\Services\CuratedMeetingPdfLayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingPdfExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_pdf_requires_authentication(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);

        $this->get('/admin/metricas/reunioes/export.pdf')->assertStatus(401);
    }

    public function test_export_pdf_blocks_non_admin_user(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);
        $user = User::factory()->create(['email' => 'viewer@example.com']);

        $this->actingAs($user)
            ->get('/admin/metricas/reunioes/export.pdf')
            ->assertStatus(403);
    }

    public function test_export_pdf_returns_pdf_for_admin_with_valid_json_source(): void
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
                        'terca' => [],
                        'quarta' => [],
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

        $response = $this->actingAs($user)->get('/admin/metricas/reunioes/export.pdf');

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('content-disposition'));
    }

    public function test_export_pdf_template_uses_inline_svg_and_no_remote_fontawesome(): void
    {
        $groups = [
            [
                'meeting_id' => '4430251323',
                'group_name' => 'Bom dia Brasil',
                'link_url' => 'https://example.org/bomdia',
                'schedule' => [
                    'segunda' => [['start' => '09:00', 'format' => 'A', 'format_badge_class' => 'pdf-badge-type-open']],
                    'terca' => [],
                    'quarta' => [],
                    'quinta' => [],
                    'sexta' => [],
                    'sabado' => [],
                    'domingo' => [],
                ],
            ],
        ];

        $layout = app(CuratedMeetingPdfLayoutService::class)->build($groups);

        $html = view('admin.metrics.pdf.curated-meetings-weekly', [
            'groups' => $groups,
            'summary' => [],
            'layout' => $layout,
            'logoDataUri' => null,
            'generatedAt' => now(),
            'exportPageWidthPt' => (float) data_get($layout, 'page_width_pt', 842),
            'exportPageHeightPt' => (float) data_get($layout, 'page_height_pt', 595),
            'weekdayColumns' => [
                'segunda' => '2ª',
                'terca' => '3ª',
                'quarta' => '4ª',
                'quinta' => '5ª',
                'sexta' => '6ª',
                'sabado' => 'SÁB',
                'domingo' => 'DOM',
            ],
        ])->render();

        $this->assertStringContainsString('link-btn-icon-svg', $html);
        $this->assertStringContainsString('<svg', $html);
        $this->assertStringContainsString('slot-marker', $html);
        $this->assertStringContainsString('EXPORT_STYLE_VERSION_V4_20260401_1', $html);
        $this->assertStringNotContainsString('font-awesome', $html);
        $this->assertStringNotContainsString('fa-solid', $html);
    }

    public function test_export_pdf_redirects_with_error_when_json_source_is_invalid(): void
    {
        config()->set('na_virtual.metrics.admin_emails', ['admin@example.com']);

        $this->mock(CuratedMeetingJsonSourceService::class, function ($mock): void {
            $mock->shouldReceive('loadValidatedGroups')->once()->andThrow(\Illuminate\Validation\ValidationException::withMessages([
                'json' => ['Erro de validacao no JSON'],
            ]));
        });

        $user = User::factory()->create(['email' => 'admin@example.com']);

        $this->from('/admin/metricas/reunioes')
            ->actingAs($user)
            ->get('/admin/metricas/reunioes/export.pdf')
            ->assertRedirect('/admin/metricas/reunioes')
            ->assertSessionHas('meeting_pdf_error');
    }
}
