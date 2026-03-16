<?php

namespace Tests\Feature;

use App\Models\VirtualMeetingSnapshot;
use App\Services\NaVirtualMeetingHomepageDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class VirtualMeetingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_index_renders_virtual_meetings_view_with_grouped_data(): void
    {
        $serverTime = Carbon::create(2026, 3, 11, 10, 0, 0, 'America/Sao_Paulo');

        $this->mock(NaVirtualMeetingHomepageDataService::class, function ($mock) use ($serverTime): void {
            $mock->shouldReceive('buildForHomepage')
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
                        'aberta' => 'público em geral',
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

        $this->mock(NaVirtualMeetingHomepageDataService::class, function ($mock) use ($serverTime): void {
            $mock->shouldReceive('buildForHomepage')
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

        $this->mock(NaVirtualMeetingHomepageDataService::class, function ($mock) use ($serverTime): void {
            $mock->shouldReceive('buildForHomepage')
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

    public function test_index_uses_latest_snapshot_when_sync_fails_and_live_data_is_empty(): void
    {
        $this->withoutExceptionHandling();
        config()->set('na_virtual.homepage_cache.key', 'na.virtual.homepage.test');
        config()->set('na_virtual.homepage_fallback.max_stale_minutes', 180);
        config()->set('na_virtual.sync_status.last_success_cache_key', 'na.virtual.sync.last_success_at.test');
        config()->set('na_virtual.sync_status.last_failure_cache_key', 'na.virtual.sync.last_failure_at.test');

        VirtualMeetingSnapshot::query()->create([
            'context' => 'na.virtual.homepage',
            'payload' => [
                'serverTime' => '2026-03-12T13:00:00-03:00',
                'runningCount' => 1,
                'startingSoonCount' => 0,
                'upcomingCount' => 0,
                'runningMeetings' => [[
                    'meeting' => [
                        'name' => 'Grupo Snapshot',
                        'meeting_platform' => 'zoom',
                        'meeting_url' => 'https://example.com/join',
                        'type_label' => 'aberta',
                        'format_labels' => ['aberta'],
                    ],
                    'start_at' => '2026-03-12T12:00:00-03:00',
                    'end_at' => '2026-03-12T14:00:00-03:00',
                    'starts_in_minutes' => 0,
                    'ends_in_minutes' => 60,
                    'status_text' => 'termina em 60 min',
                ]],
                'startingSoonMeetings' => [],
                'upcomingMeetings' => [],
                'groupedBadges' => [
                    'aberta' => 'público em geral',
                ],
            ],
            'payload_hash' => sha1('snapshot-test'),
            'captured_at' => now(),
        ]);

        Cache::put('na.virtual.sync.last_success_at.test', now()->subMinutes(10)->toIso8601String());
        Cache::put('na.virtual.sync.last_failure_at.test', now()->toIso8601String());

        Http::fake([
            'https://www.na.org.br/wp-admin/admin-ajax.php*' => Http::response('falha', 500),
        ]);

        try {
            Artisan::call('na:sync-virtual-meetings');
        } catch (\Throwable) {
            // Fallback da homepage deve cobrir indisponibilidade de sync.
        }

        $response = $this->get('/reunioes-virtuais');

        $response
            ->assertOk()
            ->assertViewHas('runningCount', 1)
            ->assertViewHas('startingSoonCount', 0)
            ->assertViewHas('upcomingCount', 0)
            ->assertSeeText('Grupo Snapshot')
            ->assertSeeText('Reuniões em andamento');
    }

    public function test_index_keeps_safe_empty_state_when_sync_fails_without_snapshot(): void
    {
        config()->set('na_virtual.homepage_cache.key', 'na.virtual.homepage.test');
        config()->set('na_virtual.homepage_fallback.max_stale_minutes', 180);
        config()->set('na_virtual.sync_status.last_success_cache_key', 'na.virtual.sync.last_success_at.test');
        config()->set('na_virtual.sync_status.last_failure_cache_key', 'na.virtual.sync.last_failure_at.test');

        Cache::put('na.virtual.sync.last_success_at.test', now()->subMinutes(10)->toIso8601String());
        Cache::put('na.virtual.sync.last_failure_at.test', now()->toIso8601String());

        Http::fake([
            'https://www.na.org.br/wp-admin/admin-ajax.php*' => Http::response('falha', 500),
        ]);

        Log::shouldReceive('warning')
            ->atLeast()
            ->once()
            ->withArgs(fn (string $message): bool => str_contains($message, 'Sincronizacao de reunioes virtuais falhou'));

        try {
            Artisan::call('na:sync-virtual-meetings');
        } catch (\Throwable) {
            // fluxo esperado para simular indisponibilidade da origem
        }

        $response = $this->get('/reunioes-virtuais');

        $response
            ->assertOk()
            ->assertViewHas('runningCount', 0)
            ->assertViewHas('startingSoonCount', 0)
            ->assertViewHas('upcomingCount', 0)
            ->assertSeeText('Nenhuma reuniao em andamento neste momento.')
            ->assertSeeText('Nenhuma reuniao iniciando na janela de tempo atual.')
            ->assertSeeText('Nenhuma reuniao futura encontrada no momento.');
    }
    public function test_index_displays_public_meeting_fields_in_clear_text(): void
    {
        config()->set('na_virtual.privacy.mask_meeting_id', true);
        config()->set('na_virtual.privacy.mask_meeting_password', true);

        $serverTime = Carbon::create(2026, 3, 12, 12, 0, 0, 'America/Sao_Paulo');
        $startAt = Carbon::create(2026, 3, 12, 12, 0, 0, 'America/Sao_Paulo');
        $endAt = Carbon::create(2026, 3, 12, 14, 0, 0, 'America/Sao_Paulo');

        $this->mock(NaVirtualMeetingHomepageDataService::class, function ($mock) use ($serverTime, $startAt, $endAt): void {
            $mock->shouldReceive('buildForHomepage')
                ->once()
                ->andReturn([
                    'serverTime' => $serverTime,
                    'runningCount' => 1,
                    'startingSoonCount' => 0,
                    'upcomingCount' => 0,
                    'runningMeetings' => new Collection([
                        [
                            'meeting' => (object) [
                                'name' => 'Grupo Seguro',
                                'meeting_platform' => 'zoom',
                                'meeting_url' => null,
                                'meeting_id' => '1234567890',
                                'meeting_password' => 'abc123',
                                'type_label' => 'aberta',
                                'format_labels' => ['aberta'],
                            ],
                            'start_at' => $startAt,
                            'end_at' => $endAt,
                            'starts_in_minutes' => 0,
                            'ends_in_minutes' => 120,
                            'status_text' => 'termina em 120 min',
                        ],
                    ]),
                    'startingSoonMeetings' => new Collection(),
                    'upcomingMeetings' => new Collection(),
                    'groupedBadges' => [],
                ]);
        });

        $response = $this->get('/reunioes-virtuais');

        $response
            ->assertOk()
            ->assertSee('1234567890')
            ->assertSee('abc123');
    }

    public function test_index_applies_web_public_rate_limit(): void
    {
        config()->set('na_virtual.rate_limit.web_public_per_minute', 2);

        $serverTime = Carbon::create(2026, 3, 11, 10, 0, 0, 'America/Sao_Paulo');

        $this->mock(NaVirtualMeetingHomepageDataService::class, function ($mock) use ($serverTime): void {
            $mock->shouldReceive('buildForHomepage')
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

        $this->get('/reunioes-virtuais')->assertOk();
        $this->get('/reunioes-virtuais')->assertOk();
        $this->get('/reunioes-virtuais')->assertStatus(429);
    }
}

