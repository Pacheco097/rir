<?php

namespace App\Enums;

/**
 * GAP/Melhoria entra no backlog Scrum.
 * Incidente crítico segue um fluxo Kanban próprio (ver IncidentStatus).
 */
enum GapType: string
{
    case Gap = 'gap';
    case Incidente = 'incidente';

    public function label(): string
    {
        return match ($this) {
            self::Gap => 'GAP/Melhoria',
            self::Incidente => 'Incidente crítico',
        };
    }

    public function codePrefix(): string
    {
        return match ($this) {
            self::Gap => 'GAP',
            self::Incidente => 'INC',
        };
    }
}
