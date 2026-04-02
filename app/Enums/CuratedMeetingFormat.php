<?php

namespace App\Enums;

enum CuratedMeetingFormat: string
{
    case FECHADA = 'F';
    case ESTUDO = 'E';
    case ABERTA = 'A';

    public function description(): string
    {
        return match ($this) {
            self::FECHADA => 'Fechada',
            self::ESTUDO => 'Estudo',
            self::ABERTA => 'Aberta',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::FECHADA => 'pdf-badge-type-closed',
            self::ESTUDO => 'pdf-badge-type-study',
            self::ABERTA => 'pdf-badge-type-open',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
