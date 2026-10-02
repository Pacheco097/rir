<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => EnumResource::make($this->role),
            'sector_id' => $this->sector_id,
            'sector' => $this->whenLoaded('sector', fn () => $this->sector?->only(['id', 'name'])),
            'job_title' => $this->job_title,
            'active' => $this->active,
        ];
    }
}
