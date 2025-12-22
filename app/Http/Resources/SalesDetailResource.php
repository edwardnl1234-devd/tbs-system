<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sales_id' => $this->sales_id,
            'stock_cpo_id' => $this->stock_cpo_id,
            'stock_kernel_id' => $this->stock_kernel_id,
            'stock_shell_id' => $this->stock_shell_id,
            'quantity_sold' => $this->quantity_sold,
            'stock_cpo' => new StockCpoResource($this->whenLoaded('stockCpo')),
            'stock_kernel' => new StockKernelResource($this->whenLoaded('stockKernel')),
            'stock_shell' => new StockShellResource($this->whenLoaded('stockShell')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
