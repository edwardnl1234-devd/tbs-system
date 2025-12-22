<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockOpnameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'opname_date' => 'required|date',
            'product_type' => 'required|in:CPO,Kernel,Shell,TBS',
            'location' => 'nullable|string|max:100',
            'physical_quantity' => 'required|numeric|min:0',
            'remarks' => 'nullable|string',
        ];
    }
}
