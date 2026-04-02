<?php

namespace Tests\Unit;

use App\Services\CuratedMeetingJsonSyncService;
use App\Services\CuratedVirtualMeetingListService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class CuratedMeetingJsonSyncServiceTest extends TestCase
{
    public function test_sync_writes_json_when_no_conflicts(): void
    {
        $path = storage_path('framework/testing/curated-sync.json');
        @mkdir(dirname($path), 0777, true);
        @unlink($path);

        config()->set('na_virtual.curated_groups.json_path', $path);

        $listService = Mockery::mock(CuratedVirtualMeetingListService::class);
        $listService->shouldReceive('loadValidatedGroups')->once()->andReturn([
            [
                'meeting_id' => '4430251323',
                'group_name' => 'Bom dia Brasil',
                'link_url' => 'https://example.org/bomdia',
                'schedule' => [
                    'segunda' => [['start' => '09:00', 'end' => '11:00', 'format' => 'A', 'format_badge_class' => 'pdf-badge-type-open']],
                    'terca' => [], 'quarta' => [], 'quinta' => [], 'sexta' => [], 'sabado' => [], 'domingo' => [],
                ],
            ],
        ]);
        $listService->shouldReceive('lastSummary')->once()->andReturn([
            'total_sheet_rows' => 15,
            'total_sheet_valid_pairs' => 15,
            'resolved_count' => 15,
            'conflicts_count' => 0,
            'conflicts' => [],
        ]);

        $service = new CuratedMeetingJsonSyncService($listService);
        $result = $service->syncFromSheetToJson();

        $this->assertSame(1, $result['written']);
        $this->assertFileExists($path);

        $decoded = json_decode((string) file_get_contents($path), true);
        $this->assertIsArray($decoded);
        $this->assertSame('Bom dia Brasil', $decoded[0]['group_name']);

        $cached = Cache::get((string) config('na_virtual.curated_groups.export_summary_cache_key'));
        $this->assertIsArray($cached);
        $this->assertSame('sheet_db_sync', data_get($cached, 'source'));

        @unlink($path);
    }

    public function test_sync_aborts_and_does_not_write_when_conflicts_exist(): void
    {
        $path = storage_path('framework/testing/curated-sync-conflict.json');
        @mkdir(dirname($path), 0777, true);
        @unlink($path);

        config()->set('na_virtual.curated_groups.json_path', $path);

        $listService = Mockery::mock(CuratedVirtualMeetingListService::class);
        $listService->shouldReceive('loadValidatedGroups')->once()->andReturn([
            ['group_name' => 'X'],
        ]);
        $listService->shouldReceive('lastSummary')->once()->andReturn([
            'total_sheet_rows' => 15,
            'total_sheet_valid_pairs' => 15,
            'resolved_count' => 14,
            'conflicts_count' => 1,
            'conflicts' => [
                ['meeting_id' => '1', 'group_name' => 'X', 'reason' => 'name_mismatch'],
            ],
        ]);

        $service = new CuratedMeetingJsonSyncService($listService);

        $this->expectException(ValidationException::class);

        try {
            $service->syncFromSheetToJson();
        } finally {
            $this->assertFileDoesNotExist($path);
        }
    }
}
