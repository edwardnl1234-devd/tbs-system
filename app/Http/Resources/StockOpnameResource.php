<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockOpnameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'opname_date' => $this->opname_date?->toDateString(),
            'product_type' => $this->product_type,
            'location' => $this->location,
            'physical_quantity' => $this->physical_quantity,
            'system_quantity' => $this->system_quantity,
            'variance' => $this->variance,
            'variance_percentage' => $this->variance_percentage,
            'counted_by' => $this->counted_by,
            'verified_by' => $this->verified_by,
            'remarks' => $this->remarks,
            'status' => $this->status,
            'counter' => new UserResource($this->whenLoaded('countedBy')),
            'verifier' => new UserResource($this->whenLoaded('verifiedBy')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
