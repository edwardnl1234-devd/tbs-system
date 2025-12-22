<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stock_tbs_id' => $this->stock_tbs_id,
            'supervisor_id' => $this->supervisor_id,
            'tbs_input_weight' => $this->tbs_input_weight,
            'cpo_output' => $this->cpo_output,
            'kernel_output' => $this->kernel_output,
            'shell_output' => $this->shell_output,
            'empty_bunch_output' => $this->empty_bunch_output,
            'cpo_extraction_rate' => $this->cpo_extraction_rate,
            'kernel_extraction_rate' => $this->kernel_extraction_rate,
            'production_date' => $this->production_date?->toDateString(),
            'shift' => $this->shift,
            'batch_number' => $this->batch_number,
            'status' => $this->status,
            'notes' => $this->notes,
            'stock_tbs' => new StockTbsResource($this->whenLoaded('stockTbs')),
            'supervisor' => new UserResource($this->whenLoaded('supervisor')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
