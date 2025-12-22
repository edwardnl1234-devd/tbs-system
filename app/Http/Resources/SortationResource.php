<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SortationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'weighing_id' => $this->weighing_id,
            'mandor_id' => $this->mandor_id,
            'good_quality_weight' => $this->good_quality_weight,
            'medium_quality_weight' => $this->medium_quality_weight,
            'poor_quality_weight' => $this->poor_quality_weight,
            'reject_weight' => $this->reject_weight,
            'assistant_deduction' => $this->assistant_deduction,
            'deduction_reason' => $this->deduction_reason,
            'final_accepted_weight' => $this->final_accepted_weight,
            'mandor_score' => $this->mandor_score,
            'operator_discipline_score' => $this->operator_discipline_score,
            'sortation_time' => $this->sortation_time?->toISOString(),
            'notes' => $this->notes,
            'weighing' => new WeighingResource($this->whenLoaded('weighing')),
            'mandor' => new UserResource($this->whenLoaded('mandor')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
