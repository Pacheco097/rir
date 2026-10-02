<?php

namespace App\Http\Resources;

use App\Models\GapComment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin GapComment */
class GapCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'sector' => $this->user->sector?->name,
                'role' => $this->user->role->label(),
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
