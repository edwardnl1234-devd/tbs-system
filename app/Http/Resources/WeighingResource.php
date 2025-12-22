<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WeighingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'queue_id' => $this->queue_id,
            'operator_id' => $this->operator_id,
            'ticket_number' => $this->ticket_number,
            'bruto_weight' => $this->bruto_weight,
            'tara_weight' => $this->tara_weight,
            'netto_weight' => $this->netto_weight,
            'price_per_kg' => $this->price_per_kg,
            'total_price' => $this->total_price,
            'weigh_in_time' => $this->weigh_in_time?->toISOString(),
            'weigh_out_time' => $this->weigh_out_time?->toISOString(),
            'status' => $this->status,
            'notes' => $this->notes,
            'queue' => new QueueResource($this->whenLoaded('queue')),
            'operator' => new UserResource($this->whenLoaded('operator')),
            'sortation' => new SortationResource($this->whenLoaded('sortation')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
