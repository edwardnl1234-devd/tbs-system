<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockAdjustmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_type' => $this->product_type,
            'system_stock' => $this->system_stock,
            'physical_stock' => $this->physical_stock,
            'difference' => $this->difference,
            'adjustment_type' => $this->adjustment_type,
            'reason' => $this->reason,
            'adjusted_by' => $this->adjusted_by,
            'approved_by' => $this->approved_by,
            'adjustment_date' => $this->adjustment_date?->toDateString(),
            'status' => $this->status,
            'adjuster' => new UserResource($this->whenLoaded('adjustedBy')),
            'approver' => new UserResource($this->whenLoaded('approvedBy')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
