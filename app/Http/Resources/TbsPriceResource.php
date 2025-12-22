<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TbsPriceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'price_date' => $this->price_date?->toDateString(),
            'price_per_kg' => $this->price_per_kg,
            'price_type' => $this->price_type,
            'quality_grade' => $this->quality_grade,
            'set_by' => $this->set_by,
            'notes' => $this->notes,
            'setter' => new UserResource($this->whenLoaded('setBy')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
