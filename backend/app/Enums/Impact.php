<?php

namespace App\Enums;

enum Impact: string
{
    case Critico = 'critico';
    case Alto = 'alto';
    case Medio = 'medio';
    case Baixo = 'baixo';

    public function label(): string
    {
        return match ($this) {
            self::Critico => 'Crítico',
            self::Alto => 'Alto',
            self::Medio => 'Médio',
            self::Baixo => 'Baixo',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Critico => 'Paralisa ou compromete significativamente a operação.',
            self::Alto => 'Afeta significativamente um processo ou setor.',
            self::Medio => 'Gera retrabalho, perda de produtividade ou inconsistência.',
            self::Baixo => 'Melhoria ou inconveniente sem impacto operacional relevante.',
        };
    }

    /** Peso usado na matriz Impacto x Urgência. */
    public function weight(): int
    {
        return match ($this) {
            self::Critico => 4,
            self::Alto => 3,
            self::Medio => 2,
            self::Baixo => 1,
        };
    }
}
