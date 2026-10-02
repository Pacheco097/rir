<?php

namespace App\Http\Resources;

use App\Models\Gap;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Versão "leve" do GAP para listagens e Kanban.
 * Separar List x Detail evita trafegar comentários/histórico sem necessidade.
 *
 * @mixin Gap
 */
class GapListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'type' => EnumResource::make($this->type),
            'title' => $this->title,
            'status' => ['value' => $this->status, 'label' => $this->statusEnum()->label()],
            'priority' => EnumResource::make($this->priority),
            'impact' => EnumResource::make($this->impact),
            'urgency' => EnumResource::make($this->urgency),
            'sector' => $this->whenLoaded('sector', fn () => $this->sector?->only(['id', 'name'])),
            'category' => $this->whenLoaded('category', fn () => $this->category?->only(['id', 'name', 'color'])),
            'it_owner' => UserSummaryResource::make($this->whenLoaded('itOwner')),
            'sector_owner' => UserSummaryResource::make($this->whenLoaded('sectorOwner')),
            'sprint' => $this->whenLoaded('sprint', fn () => $this->sprint ? [
                'id' => $this->sprint->id, 'number' => $this->sprint->number, 'name' => $this->sprint->name,
            ] : null),
            'systems' => $this->whenLoaded('systems', fn () => $this->systems->map->only(['id', 'name'])),
            'identified_at' => $this->identified_at?->format('Y-m-d'),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'is_overdue' => $this->isOverdue(),
            'tasks_count' => $this->whenCounted('tasks'),
            'comments_count' => $this->whenCounted('comments'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
