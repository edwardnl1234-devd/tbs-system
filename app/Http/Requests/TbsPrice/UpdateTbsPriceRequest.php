<?php

namespace App\Http\Requests\TbsPrice;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTbsPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'effective_date' => 'sometimes|date',
            'price_per_kg' => 'sometimes|numeric|min:0',
            'supplier_type' => 'sometimes|in:inti,plasma,umum',
            'notes' => 'nullable|string',
        ];
    }
}
