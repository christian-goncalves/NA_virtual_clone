<?php

namespace Tests\Feature;

use App\Services\NaVirtualMeetingGroupingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class VirtualMeetingControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

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

    public function test_index_uses_cached_homepage_data_on_second_request(): void
    {
        $serverTime = Carbon::create(2026, 3, 11, 10, 0, 0, 'America/Sao_Paulo');
        config()->set('na_virtual.homepage_cache.key', 'na.virtual.homepage.test');
        config()->set('na_virtual.homepage_cache.ttl_seconds', 120);

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
                    'groupedBadges' => [],
                ]);
        });

        $this->get('/reunioes-virtuais')->assertOk();
        $this->get('/reunioes-virtuais')->assertOk();
    }

    public function test_index_displays_all_three_main_sections(): void
    {
        $serverTime = Carbon::create(2026, 3, 11, 10, 0, 0, 'America/Sao_Paulo');

        $this->mock(NaVirtualMeetingGroupingService::class, function ($mock) use ($serverTime): void {
            $mock->shouldReceive('buildHomePageData')
                ->once()
                ->andReturn([
                    'serverTime' => $serverTime,
                    'runningCount' => 0,
                    'startingSoonCount' => 0,
                    'upcomingCount' => 0,
                    'runningMeetings' => new Collection(),
                    'startingSoonMeetings' => new Collection(),
                    'upcomingMeetings' => new Collection(),
                    'groupedBadges' => [],
                ]);
        });

        $response = $this->get('/reunioes-virtuais');

        $response
            ->assertOk()
            ->assertSeeText('Reuniões em andamento')
            ->assertSeeText('Iniciando em breve')
            ->assertSeeText('Próximas reuniões');
    }
}
