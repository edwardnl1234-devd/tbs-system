<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockKernelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'production_id' => $this->production_id,
            'quantity' => $this->quantity,
            'quality_grade' => $this->quality_grade,
            'location' => $this->location,
            'status' => $this->status,
            'stock_date' => $this->stock_date?->toDateString(),
            'production' => new ProductionResource($this->whenLoaded('production')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
