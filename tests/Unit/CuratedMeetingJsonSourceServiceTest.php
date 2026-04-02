<?php

namespace Tests\Unit;

use App\Services\CuratedMeetingJsonSourceService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CuratedMeetingJsonSourceServiceTest extends TestCase
{
    public function test_load_validated_groups_returns_normalized_data_for_valid_json(): void
    {
        $jsonPath = storage_path('framework/testing/curated-valid.json');
        @mkdir(dirname($jsonPath), 0777, true);

        file_put_contents($jsonPath, json_encode([
            [
                'group_name' => 'Bom dia Brasil',
                'meeting_id' => '4430251323',
                'link_url' => 'https://example.org/bomdia',
                'schedule' => [
                    'segunda' => [
                        ['start' => '19:00', 'end' => '20:30', 'format' => 'F'],
                        ['start' => '09:00', 'end' => '10:30', 'format' => 'A'],
                    ],
                    'terca' => [],
                    'quarta' => [],
                    'quinta' => [],
                    'sexta' => [],
                    'sabado' => [],
                    'domingo' => [],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        config()->set('na_virtual.curated_groups.json_path', $jsonPath);

        $service = app(CuratedMeetingJsonSourceService::class);
        $groups = $service->loadValidatedGroups();
        $summary = $service->lastSummary();

        $this->assertCount(1, $groups);
        $this->assertSame('Bom dia Brasil', data_get($groups, '0.group_name'));
        $this->assertArrayNotHasKey('city', $groups[0]);
        $this->assertSame('09:00', data_get($groups, '0.schedule.segunda.0.start'));
        $this->assertSame('A', data_get($groups, '0.schedule.segunda.0.format'));
        $this->assertSame('pdf-badge-type-open', data_get($groups, '0.schedule.segunda.0.format_badge_class'));
        $this->assertSame(1, data_get($summary, 'resolved_count'));

        @unlink($jsonPath);
    }

    public function test_load_validated_groups_rejects_invalid_format(): void
    {
        $jsonPath = storage_path('framework/testing/curated-invalid-format.json');
        @mkdir(dirname($jsonPath), 0777, true);

        file_put_contents($jsonPath, json_encode([
            [
                'group_name' => 'Bom dia Brasil',
                'link_url' => 'https://example.org/bomdia',
                'schedule' => [
                    'segunda' => [
                        ['start' => '19:00', 'end' => '20:30', 'format' => 'X'],
                    ],
                    'terca' => [],
                    'quarta' => [],
                    'quinta' => [],
                    'sexta' => [],
                    'sabado' => [],
                    'domingo' => [],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        config()->set('na_virtual.curated_groups.json_path', $jsonPath);

        $this->expectException(ValidationException::class);

        try {
            app(CuratedMeetingJsonSourceService::class)->loadValidatedGroups();
        } finally {
            @unlink($jsonPath);
        }
    }

    public function test_load_validated_groups_rejects_missing_required_fields(): void
    {
        $jsonPath = storage_path('framework/testing/curated-missing-fields.json');
        @mkdir(dirname($jsonPath), 0777, true);

        file_put_contents($jsonPath, json_encode([
            [
                'group_name' => '',
                'link_url' => '',
                'schedule' => [
                    'segunda' => [],
                    'terca' => [],
                    'quarta' => [],
                    'quinta' => [],
                    'sexta' => [],
                    'sabado' => [],
                    'domingo' => [],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        config()->set('na_virtual.curated_groups.json_path', $jsonPath);

        $this->expectException(ValidationException::class);

        try {
            app(CuratedMeetingJsonSourceService::class)->loadValidatedGroups();
        } finally {
            @unlink($jsonPath);
        }
    }
}
