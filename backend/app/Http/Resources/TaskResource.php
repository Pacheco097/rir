<?php

namespace App\Http\Resources;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Task */
class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'gap_id' => $this->gap_id,
            'title' => $this->title,
            'description' => $this->description,
            'assignee_id' => $this->assignee_id,
            'assignee' => UserSummaryResource::make($this->whenLoaded('assignee')),
            'status' => EnumResource::make($this->status),
            'due_date' => $this->due_date?->format('Y-m-d'),
        ];
    }
}
