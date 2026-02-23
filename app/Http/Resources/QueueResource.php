<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QueueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'truck_id' => $this->truck_id,
            'supplier_id' => $this->supplier_id,
            'queue_number' => $this->queue_number,
            'supplier_type' => $this->supplier_type,
            'bank' => $this->bank,
            'arrival_time' => $this->arrival_time?->toISOString(),
            'call_time' => $this->call_time?->toISOString(),
            'estimated_call_time' => $this->estimated_call_time?->toISOString(),
            'status' => $this->status,
            'notes' => $this->notes,
            'truck' => new TruckResource($this->whenLoaded('truck')),
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'weighing' => new WeighingResource($this->whenLoaded('weighing')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
