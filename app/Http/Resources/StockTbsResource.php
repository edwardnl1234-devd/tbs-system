<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockTbsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'weighing_id' => $this->weighing_id,
            'sortation_id' => $this->sortation_id,
            'quantity' => $this->quantity,
            'quality_grade' => $this->quality_grade,
            'status' => $this->status,
            'location' => $this->location,
            'received_date' => $this->received_date?->toDateString(),
            'processed_date' => $this->processed_date?->toDateString(),
            'weighing' => new WeighingResource($this->whenLoaded('weighing')),
            'sortation' => new SortationResource($this->whenLoaded('sortation')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
