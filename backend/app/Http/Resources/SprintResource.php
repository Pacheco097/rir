<?php

namespace App\Http\Resources;

use App\Models\Sprint;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Sprint */
class SprintResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'name' => $this->name,
            'label' => $this->label(),
            'goal' => $this->goal,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'status' => EnumResource::make($this->status),
            'gaps_count' => $this->whenCounted('gaps'),
            'completed_count' => $this->when(isset($this->completed_count), fn () => (int) $this->completed_count),
            'gaps' => GapListResource::collection($this->whenLoaded('gaps')),
        ];
    }
}
