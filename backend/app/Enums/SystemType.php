<?php

namespace App\Enums;

enum SystemType: string
{
    case Erp = 'erp';
    case Operacional = 'operacional';
    case Workflow = 'workflow';
    case Interno = 'interno';
    case Integracao = 'integracao';
    case Bi = 'bi';
    case Outro = 'outro';

    public function label(): string
    {
        return match ($this) {
            self::Erp => 'ERP',
            self::Operacional => 'Operacional',
            self::Workflow => 'Workflow/BPM',
            self::Interno => 'Sistema interno',
            self::Integracao => 'Integração',
            self::Bi => 'BI',
            self::Outro => 'Outro',
        };
    }
}
