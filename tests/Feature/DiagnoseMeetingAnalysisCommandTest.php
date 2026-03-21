<?php

namespace Tests\Feature;

use App\Models\MetricPageView;
use App\Models\VirtualMeeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiagnoseMeetingAnalysisCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_outputs_json_payload_in_read_only_mode(): void
    {
        $this->artisan('na:diagnose-meeting-analysis --json')
            ->assertSuccessful();
    }

    public function test_command_suggests_missing_meeting_row_id_when_clicks_have_no_matchable_context(): void
    {
        VirtualMeeting::query()->create([
            'name' => 'Grupo Diagnostico',
            'meeting_platform' => 'zoom',
            'meeting_id' => 'diag-1',
            'weekday' => 'segunda',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'duration_minutes' => 60,
            'is_open' => true,
            'is_study' => false,
            'is_lgbt' => false,
            'is_women' => false,
            'is_hybrid' => false,
            'is_active' => true,
        ]);

        MetricPageView::query()->create([
            'event_uuid' => (string) str()->ulid(),
            'occurred_at' => now()->subMinutes(5),
            'route' => '/reunioes-virtuais',
            'event_type' => 'category_click',
            'category' => 'running',
            'session_hash' => 'diag-session',
            'ip_hash' => 'diag-ip',
            'user_agent' => 'phpunit',
            'context' => [
                'meeting_name' => 'Grupo Diagnostico',
                'source_section' => 'running',
            ],
        ]);

        $this->artisan('na:diagnose-meeting-analysis --json')
            ->expectsOutputToContain('category_click_without_meeting_row_id')
            ->assertSuccessful();
    }
}
