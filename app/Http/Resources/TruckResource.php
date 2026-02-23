<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TruckResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plate_number' => $this->plate_number,
            'driver_name' => $this->driver_name,
            'driver_phone' => $this->driver_phone,
            'type' => $this->type,
            'capacity' => $this->capacity,
            'status' => $this->status,
            'is_active' => $this->status === 'active',
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
