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
            'price_date' => 'sometimes|date',
            'price_per_kg' => 'sometimes|numeric|min:0',
            'price_type' => 'sometimes|in:H-1,H-2,spot',
            'quality_grade' => 'nullable|in:A,B,C',
            'notes' => 'nullable|string',
        ];
    }
}
