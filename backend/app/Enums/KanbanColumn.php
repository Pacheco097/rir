<?php

namespace App\Enums;

/**
 * Colunas do quadro Kanban de GAPs.
 * Cada coluna agrupa um ou mais status e define o status de destino
 * quando um card é arrastado para ela.
 */
enum KanbanColumn: string
{
    case Backlog = 'backlog';
    case AFazer = 'a_fazer';
    case EmAndamento = 'em_andamento';
    case Bloqueado = 'bloqueado';
    case Homologacao = 'homologacao';
    case Concluido = 'concluido';

    public function label(): string
    {
        return match ($this) {
            self::Backlog => 'Backlog',
            self::AFazer => 'A fazer',
            self::EmAndamento => 'Em andamento',
            self::Bloqueado => 'Bloqueado',
            self::Homologacao => 'Homologação',
            self::Concluido => 'Concluído',
        };
    }

    /**
     * Status de destino ao soltar um card nesta coluna.
     * "Concluído" não aceita drop: só a homologação aprovada conclui um GAP (Regra 1).
     */
    public function targetStatus(): ?GapStatus
    {
        return match ($this) {
            self::Backlog => GapStatus::Backlog,
            self::AFazer => GapStatus::Planejado,
            self::EmAndamento => GapStatus::EmExecucao,
            self::Bloqueado => GapStatus::Bloqueado,
            self::Homologacao => GapStatus::Homologacao,
            self::Concluido => null,
        };
    }
}
