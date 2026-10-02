<?php

namespace App\Http\Resources;

use App\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Sector */
class SectorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'leader_id' => $this->leader_id,
            'leader' => UserSummaryResource::make($this->whenLoaded('leader')),
            'active' => $this->active,
            'gaps_count' => $this->whenCounted('gaps'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
