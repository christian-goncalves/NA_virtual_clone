<?php

namespace Database\Seeders;

use App\Models\MetricPageView;
use App\Models\VirtualMeeting;
use Illuminate\Database\Seeder;

class NaVirtualMeetingAnalysisQaSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        VirtualMeeting::query()
            ->where('external_id', 'like', 'qa-ma-%')
            ->delete();

        MetricPageView::query()
            ->where('session_hash', 'like', 'qa-meeting-analysis-%')
            ->delete();

        $meetings = [
            [
                'external_id' => 'qa-ma-001',
                'name' => 'Grupo Aurora',
                'meeting_id' => 'QA-1001',
                'weekday' => 'segunda',
                'start_time' => '08:00:00',
                'end_time' => '09:00:00',
                'is_open' => true,
                'is_study' => false,
            ],
            [
                'external_id' => 'qa-ma-002',
                'name' => 'Grupo Horizonte',
                'meeting_id' => 'QA-1002',
                'weekday' => 'terca',
                'start_time' => '12:00:00',
                'end_time' => '13:00:00',
                'is_open' => false,
                'is_study' => false,
            ],
            [
                'external_id' => 'qa-ma-003',
                'name' => 'Grupo Farol',
                'meeting_id' => 'QA-1003',
                'weekday' => 'quarta',
                'start_time' => '19:00:00',
                'end_time' => '20:00:00',
                'is_open' => false,
                'is_study' => true,
            ],
            [
                'external_id' => 'qa-ma-004',
                'name' => 'Grupo Viver',
                'meeting_id' => 'QA-1004',
                'weekday' => 'quinta',
                'start_time' => '21:00:00',
                'end_time' => '22:00:00',
                'is_open' => true,
                'is_study' => false,
            ],
            [
                'external_id' => 'qa-ma-005',
                'name' => 'Grupo Essencia',
                'meeting_id' => 'QA-1005',
                'weekday' => 'sexta',
                'start_time' => '06:00:00',
                'end_time' => '07:00:00',
                'is_open' => false,
                'is_study' => true,
            ],
        ];

        foreach ($meetings as $meeting) {
            VirtualMeeting::query()->create([
                'external_id' => $meeting['external_id'],
                'name' => $meeting['name'],
                'meeting_platform' => 'zoom',
                'meeting_url' => 'https://example.org/meeting/'.$meeting['meeting_id'],
                'meeting_id' => $meeting['meeting_id'],
                'weekday' => $meeting['weekday'],
                'start_time' => $meeting['start_time'],
                'end_time' => $meeting['end_time'],
                'duration_minutes' => 60,
                'is_open' => $meeting['is_open'],
                'is_study' => $meeting['is_study'],
                'is_lgbt' => false,
                'is_women' => false,
                'is_hybrid' => false,
                'is_active' => true,
                'timezone' => 'America/Sao_Paulo',
                'last_seen_at' => $now,
                'synced_at' => $now,
                'source_url' => 'https://www.na.org.br/virtual/',
                'source_hash' => 'qa-meeting-analysis',
            ]);
        }

        $clickEvents = [
            ['meeting_name' => 'Grupo Aurora', 'category' => 'running', 'minutes_ago' => 5],
            ['meeting_name' => 'Grupo Farol', 'category' => 'starting_soon', 'minutes_ago' => 12],
            ['meeting_name' => 'Grupo Essencia', 'category' => 'upcoming', 'minutes_ago' => 20],
            ['meeting_name' => 'Grupo Aurora', 'category' => 'running', 'minutes_ago' => 30],
            ['meeting_name' => 'Grupo Viver', 'category' => 'upcoming', 'minutes_ago' => 45],
        ];

        foreach ($clickEvents as $index => $event) {
            MetricPageView::query()->create([
                'occurred_at' => now()->subMinutes((int) $event['minutes_ago']),
                'route' => '/reunioes-virtuais',
                'event_type' => 'category_click',
                'category' => $event['category'],
                'session_hash' => 'qa-meeting-analysis-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'ip_hash' => 'qa-meeting-analysis-ip',
                'user_agent' => 'qa-seeder',
                'context' => [
                    'meeting_name' => $event['meeting_name'],
                    'source_section' => $event['category'],
                ],
            ]);
        }
    }
}
