<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockCpoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'production_id' => $this->production_id,
            'quantity' => $this->quantity,
            'quality_grade' => $this->quality_grade,
            'tank_number' => $this->tank_number,
            'tank_capacity' => $this->tank_capacity,
            'stock_type' => $this->stock_type,
            'movement_type' => $this->movement_type,
            'reference_number' => $this->reference_number,
            'stock_date' => $this->stock_date?->toDateString(),
            'expiry_date' => $this->expiry_date?->toDateString(),
            'status' => $this->status,
            'notes' => $this->notes,
            'production' => new ProductionResource($this->whenLoaded('production')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
