<?php

namespace Tests\Unit;

use App\Models\VirtualMeeting;
use App\Services\CuratedGroupSheetSourceService;
use App\Services\CuratedVirtualMeetingListService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CuratedVirtualMeetingListServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_load_validated_groups_matches_by_meeting_id_and_name_and_avoids_mixing(): void
    {
        VirtualMeeting::query()->create([
            'name' => 'Grupo Bom Dia Brasil',
            'meeting_id' => '4430251323',
            'city' => 'Porto Alegre',
            'meeting_url' => 'https://example.org/bomdia',
            'weekday' => 'segunda',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'is_open' => true,
            'is_study' => false,
            'is_active' => true,
        ]);

        VirtualMeeting::query()->create([
            'name' => 'Grupo Quinta Tradição',
            'meeting_id' => '4430251323',
            'city' => 'Sao Paulo',
            'meeting_url' => 'https://example.org/quinta',
            'weekday' => 'terca',
            'start_time' => '22:00:00',
            'end_time' => '23:30:00',
            'is_open' => false,
            'is_study' => false,
            'is_active' => true,
        ]);

        $this->mock(CuratedGroupSheetSourceService::class, function ($mock): void {
            $mock->shouldReceive('loadMeetingIdNamePairs')->once()->andReturn([
                'pairs' => [
                    ['meeting_id' => '4430251323', 'group_name' => 'Bom dia Brasil'],
                ],
                'total_rows_read' => 1,
                'total_valid_pairs' => 1,
            ]);
        });

        $groups = app(CuratedVirtualMeetingListService::class)->loadValidatedGroups();

        $this->assertCount(1, $groups);
        $this->assertSame('Bom dia Brasil', data_get($groups, '0.group_name'));
        $this->assertSame('4430251323', data_get($groups, '0.meeting_id'));
        $this->assertCount(1, data_get($groups, '0.schedule.segunda', []));
        $this->assertCount(0, data_get($groups, '0.schedule.terca', []));
    }

    public function test_load_validated_groups_tracks_conflict_when_name_does_not_match(): void
    {
        VirtualMeeting::query()->create([
            'name' => 'Grupo Quinta Tradição',
            'meeting_id' => '4430251323',
            'city' => 'Sao Paulo',
            'meeting_url' => 'https://example.org/quinta',
            'weekday' => 'terca',
            'start_time' => '22:00:00',
            'end_time' => '23:30:00',
            'is_open' => false,
            'is_study' => false,
            'is_active' => true,
        ]);

        $this->mock(CuratedGroupSheetSourceService::class, function ($mock): void {
            $mock->shouldReceive('loadMeetingIdNamePairs')->once()->andReturn([
                'pairs' => [
                    ['meeting_id' => '4430251323', 'group_name' => 'Nome Sem Match'],
                ],
                'total_rows_read' => 1,
                'total_valid_pairs' => 1,
            ]);
        });

        $this->expectException(ValidationException::class);

        app(CuratedVirtualMeetingListService::class)->loadValidatedGroups();
    }

    public function test_load_validated_groups_marks_ambiguous_match_when_multiple_names_fit_same_pair(): void
    {
        VirtualMeeting::query()->create([
            'name' => 'Grupo Bom Dia Brasil',
            'meeting_id' => '4430251323',
            'city' => 'Porto Alegre',
            'meeting_url' => 'https://example.org/bomdia',
            'weekday' => 'segunda',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'is_open' => true,
            'is_study' => false,
            'is_active' => true,
        ]);

        VirtualMeeting::query()->create([
            'name' => 'Grupo Bom Brasil Dia',
            'meeting_id' => '4430251323',
            'city' => 'Porto Alegre',
            'meeting_url' => 'https://example.org/bomdia2',
            'weekday' => 'terca',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'is_open' => true,
            'is_study' => false,
            'is_active' => true,
        ]);

        $this->mock(CuratedGroupSheetSourceService::class, function ($mock): void {
            $mock->shouldReceive('loadMeetingIdNamePairs')->once()->andReturn([
                'pairs' => [
                    ['meeting_id' => '4430251323', 'group_name' => 'Bom Dia Brasil'],
                ],
                'total_rows_read' => 1,
                'total_valid_pairs' => 1,
            ]);
        });

        try {
            app(CuratedVirtualMeetingListService::class)->loadValidatedGroups();
            $this->fail('Expected validation exception for ambiguous match.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('sheet', $exception->errors());
        }
    }
    public function test_load_validated_groups_resolves_by_name_even_without_id_hint(): void
    {
        VirtualMeeting::query()->create([
            'name' => 'Grupo Unidade',
            'meeting_id' => '1111111111',
            'city' => 'Rio de Janeiro',
            'meeting_url' => 'https://example.org/unidade',
            'weekday' => 'quarta',
            'start_time' => '19:00:00',
            'end_time' => '20:30:00',
            'is_open' => true,
            'is_study' => false,
            'is_active' => true,
        ]);

        $this->mock(CuratedGroupSheetSourceService::class, function ($mock): void {
            $mock->shouldReceive('loadMeetingIdNamePairs')->once()->andReturn([
                'pairs' => [
                    ['meeting_id' => null, 'group_name' => 'Unidade'],
                ],
                'total_rows_read' => 1,
                'total_valid_pairs' => 1,
            ]);
        });

        $groups = app(CuratedVirtualMeetingListService::class)->loadValidatedGroups();

        $this->assertCount(1, $groups);
        $this->assertSame('Unidade', data_get($groups, '0.group_name'));
        $this->assertSame('1111111111', data_get($groups, '0.meeting_id'));
        $this->assertCount(1, data_get($groups, '0.schedule.quarta', []));
    }
}

