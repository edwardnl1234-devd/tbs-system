<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'so_number' => $this->so_number,
            'product_type' => $this->product_type,
            'quantity' => $this->quantity,
            'price_per_kg' => $this->price_per_kg,
            'total_amount' => $this->total_amount,
            'order_date' => $this->order_date?->toDateString(),
            'delivery_date' => $this->delivery_date?->toDateString(),
            'truck_plate' => $this->truck_plate,
            'driver_name' => $this->driver_name,
            'status' => $this->status,
            'notes' => $this->notes,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'details' => SalesDetailResource::collection($this->whenLoaded('details')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
