<?php

namespace App\Enums;

enum Priority: string
{
    case Critica = 'critica';
    case Alta = 'alta';
    case Media = 'media';
    case Baixa = 'baixa';

    public function label(): string
    {
        return match ($this) {
            self::Critica => 'Crítica',
            self::Alta => 'Alta',
            self::Media => 'Média',
            self::Baixa => 'Baixa',
        };
    }

    /** Usado para ordenar (maior = mais prioritário). */
    public function rank(): int
    {
        return match ($this) {
            self::Critica => 4,
            self::Alta => 3,
            self::Media => 2,
            self::Baixa => 1,
        };
    }

    /**
     * Sugere a prioridade a partir da matriz Impacto x Urgência
     * (técnica comum em ITIL/Service Desk). Score de 2 a 8.
     */
    public static function suggest(Impact $impact, Urgency $urgency): self
    {
        $score = $impact->weight() + $urgency->weight();

        return match (true) {
            $score >= 7 => self::Critica,
            $score >= 5 => self::Alta,
            $score >= 4 => self::Media,
            default => self::Baixa,
        };
    }
}
