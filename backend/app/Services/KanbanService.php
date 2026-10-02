<?php

namespace App\Services;

use App\Enums\GapStatus;
use App\Enums\GapType;
use App\Enums\KanbanColumn;
use App\Exceptions\BusinessRuleException;
use App\Models\Gap;
use App\Models\User;
use Illuminate\Support\Collection;

class KanbanService
{
    public function __construct(private readonly GapService $gaps) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array{key:string,label:string,gaps:Collection<int, Gap>}>
     */
    public function board(User $user, array $filters): Collection
    {
        $gaps = Gap::query()
            ->visibleTo($user)
            ->where('type', GapType::Gap->value)
            ->where('status', '!=', GapStatus::Cancelado->value)
            // Concluídos só dos últimos 30 dias, para o quadro não crescer infinitamente.
            ->where(fn ($q) => $q->where('status', '!=', GapStatus::Concluido->value)
                ->orWhere('completed_at', '>=', now()->subDays(30)))
            ->filter($filters)
            ->with(['sector:id,name', 'itOwner:id,name', 'sprint:id,number,name', 'systems:id,name'])
            ->get()
            ->sortBy([
                fn (Gap $a, Gap $b) => $b->priority->rank() <=> $a->priority->rank(),
                fn (Gap $a, Gap $b) => ($a->due_date?->timestamp ?? PHP_INT_MAX) <=> ($b->due_date?->timestamp ?? PHP_INT_MAX),
            ]);

        return collect(KanbanColumn::cases())->map(fn (KanbanColumn $column) => [
            'key' => $column->value,
            'label' => $column->label(),
            'gaps' => $gaps->filter(fn (Gap $g) => GapStatus::from($g->status)->kanbanColumn() === $column)->values(),
        ]);
    }

    public function move(Gap $gap, KanbanColumn $column, User $user, ?string $reason = null): Gap
    {
        $target = $column->targetStatus()
            ?? throw new BusinessRuleException('Para concluir, o GAP precisa ser aprovado na homologação pelo setor.');

        return $this->gaps->transition($gap, $target->value, $user, $reason);
    }
}
