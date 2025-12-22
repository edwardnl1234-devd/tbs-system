<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockCpoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'production_id' => 'nullable|exists:productions,id',
            'quantity' => 'required|numeric|min:0',
            'quality_grade' => 'nullable|in:premium,standard,low',
            'tank_number' => 'nullable|string|max:20',
            'tank_capacity' => 'nullable|numeric|min:0',
            'stock_type' => 'nullable|in:production,persediaan,reserved',
            'movement_type' => 'required|in:in,out,adjustment',
            'reference_number' => 'nullable|string|max:50',
            'stock_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:stock_date',
            'status' => 'nullable|in:available,reserved,sold,transit',
            'notes' => 'nullable|string',
        ];
    }
}
