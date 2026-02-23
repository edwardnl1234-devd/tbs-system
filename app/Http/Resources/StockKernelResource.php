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
            'supplier_id' => $this->supplier_id,
            'quantity' => $this->quantity,
            'purchase_price' => $this->purchase_price,
            'quality_grade' => $this->quality_grade,
            'location' => $this->location,
            'stock_type' => $this->stock_type,
            'status' => $this->status,
            'purchase_status' => $this->purchase_status,
            'stock_date' => $this->stock_date?->toDateString(),
            'production' => new ProductionResource($this->whenLoaded('production')),
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
