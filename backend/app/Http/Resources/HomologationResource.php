<?php

namespace App\Http\Resources;

use App\Models\Homologation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Homologation */
class HomologationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'submitted_by' => UserSummaryResource::make($this->whenLoaded('submitter')),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'tested_by' => UserSummaryResource::make($this->whenLoaded('tester')),
            'tested_at' => $this->tested_at?->toIso8601String(),
            'result' => EnumResource::make($this->result),
            'test_result' => $this->test_result,
            'notes' => $this->notes,
        ];
    }
}
