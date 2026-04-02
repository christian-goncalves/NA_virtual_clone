<?php

namespace Tests\Unit;

use App\Services\CuratedGroupSheetSourceService;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CuratedGroupSheetSourceServiceTest extends TestCase
{
    public function test_load_meeting_id_name_pairs_returns_valid_pairs(): void
    {
        config()->set('na_virtual.curated_groups.sheets.spreadsheet_id', 'fake-sheet');
        config()->set('na_virtual.curated_groups.sheets.gid', '0');

        Http::fake([
            '*' => Http::response("Coluna 1,GRUPOS,ID\n1,Grupo Alpha,4430251323\n2,Grupo Beta,\n", 200),
        ]);

        $result = app(CuratedGroupSheetSourceService::class)->loadMeetingIdNamePairs();

        $this->assertSame(2, $result['total_rows_read']);
        $this->assertSame(2, $result['total_valid_pairs']);
        $this->assertSame('4430251323', data_get($result, 'pairs.0.meeting_id'));
        $this->assertSame('Grupo Alpha', data_get($result, 'pairs.0.group_name'));
        $this->assertNull(data_get($result, 'pairs.1.meeting_id'));
        $this->assertSame('Grupo Beta', data_get($result, 'pairs.1.group_name'));
    }

    public function test_load_meeting_id_name_pairs_accepts_same_id_with_different_names(): void
    {
        config()->set('na_virtual.curated_groups.sheets.spreadsheet_id', 'fake-sheet');
        config()->set('na_virtual.curated_groups.sheets.gid', '0');

        Http::fake([
            '*' => Http::response("Coluna 1,GRUPOS,ID\n1,Grupo Alpha,4430251323\n2,Grupo Outra Nomenclatura,4430251323\n", 200),
        ]);

        $result = app(CuratedGroupSheetSourceService::class)->loadMeetingIdNamePairs();

        $this->assertSame(2, $result['total_valid_pairs']);
        $this->assertSame('Grupo Alpha', data_get($result, 'pairs.0.group_name'));
        $this->assertSame('Grupo Outra Nomenclatura', data_get($result, 'pairs.1.group_name'));
    }

    public function test_load_meeting_id_name_pairs_deduplicates_by_group_name(): void
    {
        config()->set('na_virtual.curated_groups.sheets.spreadsheet_id', 'fake-sheet');
        config()->set('na_virtual.curated_groups.sheets.gid', '0');

        Http::fake([
            '*' => Http::response("Coluna 1,GRUPOS,ID\n1,Grupo Alpha,4430251323\n2,Grupo Alpha,9999999999\n", 200),
        ]);

        $result = app(CuratedGroupSheetSourceService::class)->loadMeetingIdNamePairs();

        $this->assertSame(1, $result['total_valid_pairs']);
        $this->assertSame('Grupo Alpha', data_get($result, 'pairs.0.group_name'));
    }

    public function test_load_meeting_id_name_pairs_rejects_missing_name_or_invalid_id(): void
    {
        config()->set('na_virtual.curated_groups.sheets.spreadsheet_id', 'fake-sheet');
        config()->set('na_virtual.curated_groups.sheets.gid', '0');

        Http::fake([
            '*' => Http::response("Coluna 1,GRUPOS,ID\n1,,123\n2,Grupo Alpha,ABC\n", 200),
        ]);

        $this->expectException(ValidationException::class);

        app(CuratedGroupSheetSourceService::class)->loadMeetingIdNamePairs();
    }
}
