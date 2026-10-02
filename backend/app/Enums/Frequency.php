<?php

namespace App\Enums;

enum Frequency: string
{
    case Unica = 'unica';
    case Eventual = 'eventual';
    case Semanal = 'semanal';
    case Diaria = 'diaria';
    case Continua = 'continua';

    public function label(): string
    {
        return match ($this) {
            self::Unica => 'Ocorrência única',
            self::Eventual => 'Eventual',
            self::Semanal => 'Semanal',
            self::Diaria => 'Diária',
            self::Continua => 'Contínua',
        };
    }
}
