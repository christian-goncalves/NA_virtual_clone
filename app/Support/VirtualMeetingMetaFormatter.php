<?php

namespace App\Support;

class VirtualMeetingMetaFormatter
{
    /**
     * @return list<string>
     */
    public static function buildMetaParts(mixed $meetingData): array
    {
        $platform = data_get($meetingData, 'meeting.meeting_platform');
        $meetingId = data_get($meetingData, 'meeting.meeting_id');
        $meetingPassword = data_get($meetingData, 'meeting.meeting_password');

        $parts = [];

        if (is_string($platform) && trim($platform) !== '') {
            $parts[] = ucfirst(trim($platform));
        }

        if (is_string($meetingId) && trim($meetingId) !== '') {
            $parts[] = 'ID: '.trim($meetingId);
        }

        if (is_string($meetingPassword) && trim($meetingPassword) !== '') {
            $parts[] = 'Senha: '.trim($meetingPassword);
        }

        return $parts;
    }

    public static function buildMetaLine(mixed $meetingData): string
    {
        $parts = self::buildMetaParts($meetingData);

        return $parts !== [] ? implode(' | ', $parts) : 'Plataforma nao informada';
    }
}
