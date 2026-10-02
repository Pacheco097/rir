<?php

namespace App\Enums;

enum MeetingStatus: string
{
    case Agendada = 'agendada';
    case Confirmada = 'confirmada';
    case Realizada = 'realizada';
    case Cancelada = 'cancelada';
    case Reagendada = 'reagendada';

    public function label(): string
    {
        return match ($this) {
            self::Agendada => 'Agendada',
            self::Confirmada => 'Confirmada',
            self::Realizada => 'Realizada',
            self::Cancelada => 'Cancelada',
            self::Reagendada => 'Reagendada',
        };
    }
}
