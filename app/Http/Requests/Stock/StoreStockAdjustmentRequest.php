<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_type' => 'required|in:CPO,Kernel,Shell,TBS',
            'system_stock' => 'required|numeric',
            'physical_stock' => 'required|numeric',
            'adjustment_type' => 'required|in:plus,minus,correction',
            'reason' => 'nullable|string',
            'adjustment_date' => 'required|date',
        ];
    }
}
