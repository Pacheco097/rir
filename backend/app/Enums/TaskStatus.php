<?php

namespace App\Enums;

enum TaskStatus: string
{
    case AFazer = 'a_fazer';
    case EmAndamento = 'em_andamento';
    case Concluida = 'concluida';

    public function label(): string
    {
        return match ($this) {
            self::AFazer => 'A fazer',
            self::EmAndamento => 'Em andamento',
            self::Concluida => 'Concluída',
        };
    }
}
