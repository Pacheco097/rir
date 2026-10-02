<?php

namespace App\Enums;

enum Urgency: string
{
    case Imediata = 'imediata';
    case CurtoPrazo = 'curto_prazo';
    case MedioPrazo = 'medio_prazo';
    case SemUrgencia = 'sem_urgencia';

    public function label(): string
    {
        return match ($this) {
            self::Imediata => 'Imediata',
            self::CurtoPrazo => 'Curto prazo',
            self::MedioPrazo => 'Médio prazo',
            self::SemUrgencia => 'Sem urgência',
        };
    }

    public function weight(): int
    {
        return match ($this) {
            self::Imediata => 4,
            self::CurtoPrazo => 3,
            self::MedioPrazo => 2,
            self::SemUrgencia => 1,
        };
    }
}
