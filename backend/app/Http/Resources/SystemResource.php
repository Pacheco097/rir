<?php

namespace App\Http\Resources;

use App\Models\System;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin System */
class SystemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => EnumResource::make($this->type),
            'owner_id' => $this->owner_id,
            'owner' => UserSummaryResource::make($this->whenLoaded('owner')),
            'active' => $this->active,
            'gaps_count' => $this->whenCounted('gaps'),
        ];
    }
}
