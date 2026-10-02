<?php

namespace App\Http\Resources;

use App\Models\GapHistory;
use App\Observers\GapObserver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin GapHistory */
class GapHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'field' => $this->field,
            'field_label' => GapObserver::TRACKED[$this->field] ?? match ($this->field) {
                'criado' => 'Criação',
                'systems' => 'Sistemas',
                default => $this->field,
            },
            'old_value' => $this->old_value,
            'new_value' => $this->new_value,
            'user' => UserSummaryResource::make($this->whenLoaded('user')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
