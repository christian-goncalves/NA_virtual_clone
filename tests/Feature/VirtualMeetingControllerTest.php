<?php

namespace Tests\Feature;

use App\Services\NaVirtualMeetingGroupingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class VirtualMeetingControllerTest extends TestCase
{
    public function test_index_renders_virtual_meetings_view_with_grouped_data(): void
    {
        $serverTime = Carbon::create(2026, 3, 11, 10, 0, 0, 'America/Sao_Paulo');

        $this->mock(NaVirtualMeetingGroupingService::class, function ($mock) use ($serverTime): void {
            $mock->shouldReceive('buildHomePageData')
                ->once()
                ->andReturn([
                    'serverTime' => $serverTime,
                    'runningCount' => 1,
                    'startingSoonCount' => 2,
                    'upcomingCount' => 3,
                    'runningMeetings' => new Collection(),
                    'startingSoonMeetings' => new Collection(),
                    'upcomingMeetings' => new Collection(),
                    'groupedBadges' => [
                        'aberta' => 'Aberta - publico em geral',
                    ],
                ]);
        });

        $response = $this->get('/reunioes-virtuais');

        $response
            ->assertOk()
            ->assertViewIs('virtual-meetings.index')
            ->assertViewHas('runningCount', 1)
            ->assertViewHas('startingSoonCount', 2)
            ->assertViewHas('upcomingCount', 3)
            ->assertSee('Reunioes Virtuais');
    }
}