<?php

namespace App\Enums;

enum SprintStatus: string
{
    case Planejamento = 'planejamento';
    case EmAndamento = 'em_andamento';
    case Encerrada = 'encerrada';

    public function label(): string
    {
        return match ($this) {
            self::Planejamento => 'Planejamento',
            self::EmAndamento => 'Em andamento',
            self::Encerrada => 'Encerrada',
        };
    }
}
