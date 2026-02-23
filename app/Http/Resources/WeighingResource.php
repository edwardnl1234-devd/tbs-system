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
            'product_type' => $this->product_type,
            'bruto_weight' => $this->bruto_weight,
            'tara_weight' => $this->tara_weight,
            'netto_weight' => $this->netto_weight,
            // Derivative weights (Hasil Turunan TBS)
            'cpo_weight' => $this->cpo_weight,
            'kernel_weight' => $this->kernel_weight,
            'cangkang_weight' => $this->cangkang_weight,
            'fiber_weight' => $this->fiber_weight,
            'jangkos_weight' => $this->jangkos_weight,
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
