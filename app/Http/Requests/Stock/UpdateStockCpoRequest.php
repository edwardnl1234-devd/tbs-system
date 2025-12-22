<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockCpoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => 'sometimes|numeric|min:0',
            'quality_grade' => 'nullable|in:premium,standard,low',
            'tank_number' => 'nullable|string|max:20',
            'tank_capacity' => 'nullable|numeric|min:0',
            'stock_type' => 'nullable|in:production,persediaan,reserved',
            'status' => 'sometimes|in:available,reserved,sold,transit',
            'notes' => 'nullable|string',
        ];
    }
}
