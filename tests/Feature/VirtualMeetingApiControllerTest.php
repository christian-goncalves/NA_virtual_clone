<?php

namespace Tests\Feature;

use App\Models\VirtualMeetingSnapshot;
use App\Services\NaVirtualMeetingHomepageDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class VirtualMeetingApiControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_api_returns_contract_payload_with_json_content_type(): void
    {
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
                                'name' => 'Grupo API',
                                'meeting_platform' => 'zoom',
                                'meeting_url' => 'https://example.com/reuniao',
                                'type_label' => 'aberta',
                                'format_labels' => ['aberta', 'virtual'],
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
                    'groupedBadges' => [
                        'aberta' => 'público em geral',
                    ],
                ]);
        });

        $response = $this->getJson('/api/reunioes-virtuais');

        $response
            ->assertOk()
            ->assertHeader('content-type', 'application/json')
            ->assertJsonStructure([
                'serverTime',
                'runningCount',
                'startingSoonCount',
                'upcomingCount',
                'runningMeetings',
                'startingSoonMeetings',
                'upcomingMeetings',
                'groupedBadges',
            ])
            ->assertJsonPath('runningCount', 1)
            ->assertJsonPath('runningMeetings.0.meeting.name', 'Grupo API')
            ->assertJsonPath('runningMeetings.0.meeting.meeting_platform', 'zoom')
            ->assertJsonPath('runningMeetings.0.meeting.format_labels.0', 'aberta')
            ->assertJsonPath('groupedBadges.aberta', 'público em geral');
    }

    public function test_api_contract_has_expected_types_and_minimum_meeting_item_structure(): void
    {
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
                                'name' => 'Grupo Estrutura API',
                                'meeting_platform' => 'zoom',
                                'meeting_url' => 'https://example.com/estrutura',
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
                    'groupedBadges' => [
                        'aberta' => 'público em geral',
                    ],
                ]);
        });

        $response = $this->getJson('/api/reunioes-virtuais');
        $json = $response->json();

        $response->assertOk();
        $this->assertIsArray($json);
        $this->assertIsString($json['serverTime']);
        $this->assertIsInt($json['runningCount']);
        $this->assertIsInt($json['startingSoonCount']);
        $this->assertIsInt($json['upcomingCount']);
        $this->assertIsArray($json['runningMeetings']);
        $this->assertIsArray($json['startingSoonMeetings']);
        $this->assertIsArray($json['upcomingMeetings']);
        $this->assertIsArray($json['groupedBadges']);

        $this->assertNotEmpty($json['runningMeetings']);
        $first = $json['runningMeetings'][0];
        $this->assertIsArray($first);
        $this->assertArrayHasKey('meeting', $first);
        $this->assertArrayHasKey('start_at', $first);
        $this->assertArrayHasKey('end_at', $first);
        $this->assertArrayHasKey('starts_in_minutes', $first);
        $this->assertArrayHasKey('ends_in_minutes', $first);
        $this->assertArrayHasKey('status_text', $first);

        $this->assertIsArray($first['meeting']);
        $this->assertArrayHasKey('name', $first['meeting']);
        $this->assertArrayHasKey('meeting_platform', $first['meeting']);
        $this->assertArrayHasKey('meeting_url', $first['meeting']);
        $this->assertArrayHasKey('type_label', $first['meeting']);
        $this->assertArrayHasKey('format_labels', $first['meeting']);
        $this->assertIsArray($first['meeting']['format_labels']);
    }

    public function test_api_uses_snapshot_fallback_without_breaking_contract(): void
    {
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
                        'id' => 888,
                        'name' => 'Grupo Snapshot API',
                        'meeting_platform' => 'zoom',
                        'meeting_url' => 'https://example.com/snapshot',
                        'meeting_id' => 'snap-api-888',
                        'weekday' => 'segunda',
                        'start_time' => '12:00:00',
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
            'payload_hash' => sha1('snapshot-api-test'),
            'captured_at' => now(),
        ]);

        Cache::put('na.virtual.sync.last_success_at.test', now()->subMinutes(10)->toIso8601String());
        Cache::put('na.virtual.sync.last_failure_at.test', now()->toIso8601String());

        $response = $this->getJson('/api/reunioes-virtuais');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'serverTime',
                'runningCount',
                'startingSoonCount',
                'upcomingCount',
                'runningMeetings',
                'startingSoonMeetings',
                'upcomingMeetings',
                'groupedBadges',
            ])
            ->assertJsonPath('runningCount', 1)
            ->assertJsonPath('runningMeetings.0.meeting.name', 'Grupo Snapshot API')
            ->assertJsonPath('groupedBadges.aberta', 'público em geral');
    }


    public function test_server_time_endpoint_returns_iso8601_payload(): void
    {
        $response = $this->getJson('/api/server-time');

        $response
            ->assertOk()
            ->assertJsonStructure(['serverTime']);

        $serverTime = (string) $response->json('serverTime');

        $this->assertNotSame('', $serverTime);
        $this->assertNotNull(Carbon::parse($serverTime));
    }

    public function test_api_applies_public_rate_limit(): void
    {
        config()->set('na_virtual.rate_limit.api_public_per_minute', 2);

        $serverTime = Carbon::create(2026, 3, 12, 12, 0, 0, 'America/Sao_Paulo');

        $this->mock(NaVirtualMeetingHomepageDataService::class, function ($mock) use ($serverTime): void {
            $mock->shouldReceive('buildForHomepage')
                ->times(2)
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

        $this->getJson('/api/reunioes-virtuais')->assertOk();
        $this->getJson('/api/reunioes-virtuais')->assertOk();
        $this->getJson('/api/reunioes-virtuais')->assertStatus(429);
    }
}
