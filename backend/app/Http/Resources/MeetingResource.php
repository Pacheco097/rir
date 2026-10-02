<?php

namespace App\Http\Resources;

use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Meeting */
class MeetingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'sector_id' => $this->sector_id,
            'sector' => $this->whenLoaded('sector', fn () => $this->sector?->only(['id', 'name'])),
            'leader_id' => $this->leader_id,
            'leader' => UserSummaryResource::make($this->whenLoaded('leader')),
            'it_owner_id' => $this->it_owner_id,
            'it_owner' => UserSummaryResource::make($this->whenLoaded('itOwner')),
            'date' => $this->date?->format('Y-m-d'),
            'start_time' => $this->start_time ? substr($this->start_time, 0, 5) : null,
            'end_time' => $this->end_time ? substr($this->end_time, 0, 5) : null,
            'objective' => $this->objective,
            'status' => EnumResource::make($this->status),
            'notes' => $this->notes,
            // Garante que a pauta sempre traga os 8 tópicos, mesmo que vazios.
            'agenda' => collect(Meeting::AGENDA_TOPICS)->map(fn ($label, $key) => [
                'key' => $key,
                'label' => $label,
                'content' => $this->agenda[$key] ?? null,
            ])->values(),
            'minutes' => $this->minutes,
            'summary' => $this->summary,
            'decisions' => $this->decisions,
            'next_steps' => $this->next_steps,
            'previous_meeting_id' => $this->previous_meeting_id,
            'participants' => UserSummaryResource::collection($this->whenLoaded('participants')),
            'gaps' => GapListResource::collection($this->whenLoaded('gaps')),
            'gaps_count' => $this->whenCounted('gaps'),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
        ];
    }
}
