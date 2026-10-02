<?php

namespace App\Enums;

/**
 * Workflow do GAP/Melhoria.
 *
 * O mapa de transições permitidas (transitions) é uma "máquina de estados":
 * em vez de espalhar ifs pelo sistema, uma única função responde
 * "posso ir de X para Y?".
 */
enum GapStatus: string
{
    case Levantado = 'levantado';
    case Triagem = 'triagem';
    case Analise = 'analise';
    case Priorizado = 'priorizado';
    case Backlog = 'backlog';
    case Planejado = 'planejado';
    case EmExecucao = 'em_execucao';
    case Homologacao = 'homologacao';
    case Concluido = 'concluido';
    case Cancelado = 'cancelado';
    case Bloqueado = 'bloqueado';
    case Reaberto = 'reaberto';

    public function label(): string
    {
        return match ($this) {
            self::Levantado => 'Levantado',
            self::Triagem => 'Triagem',
            self::Analise => 'Análise',
            self::Priorizado => 'Priorizado',
            self::Backlog => 'Backlog',
            self::Planejado => 'Planejado',
            self::EmExecucao => 'Em execução',
            self::Homologacao => 'Homologação',
            self::Concluido => 'Concluído',
            self::Cancelado => 'Cancelado',
            self::Bloqueado => 'Bloqueado',
            self::Reaberto => 'Reaberto',
        };
    }

    /** @return list<self> */
    public function transitions(): array
    {
        return match ($this) {
            self::Levantado => [self::Triagem, self::Cancelado],
            self::Triagem => [self::Analise, self::Priorizado, self::Cancelado, self::Bloqueado],
            self::Analise => [self::Priorizado, self::Cancelado, self::Bloqueado],
            self::Priorizado => [self::Backlog, self::Planejado, self::Cancelado, self::Bloqueado],
            self::Backlog => [self::Planejado, self::Priorizado, self::Cancelado, self::Bloqueado],
            self::Planejado => [self::EmExecucao, self::Backlog, self::Cancelado, self::Bloqueado],
            self::EmExecucao => [self::Homologacao, self::Planejado, self::Bloqueado, self::Cancelado],
            // Saída da homologação acontece pelo aceite/recusa do setor (HomologationService).
            self::Homologacao => [self::EmExecucao],
            self::Bloqueado => [self::Triagem, self::Analise, self::Backlog, self::Planejado, self::EmExecucao, self::Cancelado],
            self::Reaberto => [self::Triagem, self::Analise, self::Planejado, self::EmExecucao, self::Cancelado],
            self::Concluido => [self::Reaberto],
            self::Cancelado => [self::Reaberto],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->transitions(), true);
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Concluido, self::Cancelado], true);
    }

    /** Status a partir dos quais é obrigatório ter Responsável TI (Regra 2). */
    public function requiresItOwner(): bool
    {
        return in_array($this, [
            self::Priorizado, self::Backlog, self::Planejado, self::EmExecucao,
            self::Homologacao, self::Concluido,
        ], true);
    }

    public function kanbanColumn(): KanbanColumn
    {
        return match ($this) {
            self::Planejado => KanbanColumn::AFazer,
            self::EmExecucao => KanbanColumn::EmAndamento,
            self::Bloqueado => KanbanColumn::Bloqueado,
            self::Homologacao => KanbanColumn::Homologacao,
            self::Concluido, self::Cancelado => KanbanColumn::Concluido,
            default => KanbanColumn::Backlog,
        };
    }

    /** @return list<string> */
    public static function openValues(): array
    {
        return array_values(array_map(
            fn (self $s) => $s->value,
            array_filter(self::cases(), fn (self $s) => $s->isOpen()),
        ));
    }
}
