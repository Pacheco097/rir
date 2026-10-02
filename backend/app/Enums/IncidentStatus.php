<?php

namespace App\Enums;

/** Fluxo Kanban dos incidentes críticos. */
enum IncidentStatus: string
{
    case Novo = 'novo';
    case Triagem = 'triagem';
    case EmAtendimento = 'em_atendimento';
    case Aguardando = 'aguardando';
    case Resolvido = 'resolvido';
    case Validacao = 'validacao';
    case Encerrado = 'encerrado';

    public function label(): string
    {
        return match ($this) {
            self::Novo => 'Novo',
            self::Triagem => 'Triagem',
            self::EmAtendimento => 'Em atendimento',
            self::Aguardando => 'Aguardando',
            self::Resolvido => 'Resolvido',
            self::Validacao => 'Validação',
            self::Encerrado => 'Encerrado',
        };
    }

    /** @return list<self> */
    public function transitions(): array
    {
        return match ($this) {
            self::Novo => [self::Triagem],
            self::Triagem => [self::EmAtendimento, self::Aguardando],
            self::EmAtendimento => [self::Aguardando, self::Resolvido],
            self::Aguardando => [self::EmAtendimento],
            self::Resolvido => [self::Validacao, self::EmAtendimento],
            self::Validacao => [self::Encerrado, self::EmAtendimento],
            self::Encerrado => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->transitions(), true);
    }

    public function isOpen(): bool
    {
        return $this !== self::Encerrado;
    }
}
