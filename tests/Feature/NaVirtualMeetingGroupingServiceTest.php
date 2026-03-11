<?php

namespace Tests\Feature;

use App\Models\VirtualMeeting;
use App\Services\NaVirtualMeetingGroupingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NaVirtualMeetingGroupingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_groups_running_starting_soon_and_upcoming_meetings(): void
    {
        $this->createMeeting([
            'name' => 'Em andamento',
            'weekday' => 'quarta',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        $this->createMeeting([
            'name' => 'Inicia em breve',
            'weekday' => 'quarta',
            'start_time' => '11:30:00',
            'end_time' => '13:00:00',
        ]);

        $this->createMeeting([
            'name' => 'Proxima',
            'weekday' => 'quarta',
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
        ]);

        $service = app(NaVirtualMeetingGroupingService::class);
        $now = Carbon::create(2026, 3, 11, 11, 0, 0, 'America/Sao_Paulo'); // quarta

        $grouped = $service->groupMeetings($now, 60);

        $this->assertCount(1, $grouped['running']);
        $this->assertCount(1, $grouped['startingSoon']);
        $this->assertCount(1, $grouped['upcoming']);

        $this->assertSame('Em andamento', $grouped['running']->first()['meeting']->name);
        $this->assertSame('Inicia em breve', $grouped['startingSoon']->first()['meeting']->name);
        $this->assertSame('Proxima', $grouped['upcoming']->first()['meeting']->name);
    }

    public function test_treats_overnight_meeting_as_running_after_midnight(): void
    {
        $this->createMeeting([
            'name' => 'Madrugada',
            'weekday' => 'terça',
            'start_time' => '23:00:00',
            'end_time' => '01:00:00',
        ]);

        $service = app(NaVirtualMeetingGroupingService::class);
        $now = Carbon::create(2026, 3, 11, 0, 30, 0, 'America/Sao_Paulo'); // quarta 00:30

        $grouped = $service->groupMeetings($now, 60);

        $this->assertCount(1, $grouped['running']);
        $this->assertSame('Madrugada', $grouped['running']->first()['meeting']->name);
    }

    public function test_build_home_page_data_returns_expected_view_model_keys(): void
    {
        $this->createMeeting([
            'name' => 'Meeting A',
            'weekday' => 'quarta',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        $service = app(NaVirtualMeetingGroupingService::class);
        $now = Carbon::create(2026, 3, 11, 10, 30, 0, 'America/Sao_Paulo');

        $data = $service->buildHomePageData($now, 60);

        $this->assertArrayHasKey('serverTime', $data);
        $this->assertArrayHasKey('runningCount', $data);
        $this->assertArrayHasKey('startingSoonCount', $data);
        $this->assertArrayHasKey('upcomingCount', $data);
        $this->assertArrayHasKey('runningMeetings', $data);
        $this->assertArrayHasKey('startingSoonMeetings', $data);
        $this->assertArrayHasKey('upcomingMeetings', $data);
        $this->assertArrayHasKey('groupedBadges', $data);
        $this->assertSame(1, $data['runningCount']);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createMeeting(array $overrides = []): VirtualMeeting
    {
        $defaults = [
            'external_id' => sha1(uniqid('meeting', true)),
            'name' => 'Meeting',
            'meeting_platform' => 'zoom',
            'meeting_url' => 'https://zoom.us/j/123',
            'weekday' => 'quarta',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'duration_minutes' => 60,
            'timezone' => 'America/Sao_Paulo',
            'is_active' => true,
            'source_url' => 'https://www.na.org.br/virtual/',
        ];

        return VirtualMeeting::query()->create(array_merge($defaults, $overrides));
    }
}
