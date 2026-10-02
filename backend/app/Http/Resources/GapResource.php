<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/** Detalhe completo do GAP. @mixin \App\Models\Gap */
class GapResource extends GapListResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'description' => $this->description,
            'process' => $this->process,
            'origin' => EnumResource::make($this->origin),
            'frequency' => EnumResource::make($this->frequency),
            'critical_justification' => $this->critical_justification,
            'notes' => $this->notes,
            'sector_id' => $this->sector_id,
            'category_id' => $this->category_id,
            'it_owner_id' => $this->it_owner_id,
            'sector_owner_id' => $this->sector_owner_id,
            'sprint_id' => $this->sprint_id,
            'solution' => $this->solution,
            'solution_evidence' => $this->solution_evidence,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'completed_by' => UserSummaryResource::make($this->whenLoaded('completer')),
            'created_by' => UserSummaryResource::make($this->whenLoaded('creator')),
            'meetings' => $this->whenLoaded('meetings', fn () => $this->meetings->map(fn ($m) => [
                'id' => $m->id, 'title' => $m->title, 'date' => $m->date?->format('Y-m-d'),
            ])),
            'related_gaps' => GapListResource::collection($this->whenLoaded('relatedGaps')),
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
            'homologations' => HomologationResource::collection($this->whenLoaded('homologations')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'allowed_transitions' => $this->allowedTransitions(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /** Próximos status possíveis — o frontend usa para montar os botões de ação. */
    private function allowedTransitions(): array
    {
        return array_map(
            fn ($s) => ['value' => $s->value, 'label' => $s->label()],
            $this->statusEnum()->transitions(),
        );
    }
}
