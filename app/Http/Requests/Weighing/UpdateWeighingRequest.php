<?php

namespace App\Http\Requests\Weighing;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWeighingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bruto_weight' => 'nullable|numeric|min:0',
            'tara_weight' => 'nullable|numeric|min:0',
            'netto_weight' => 'nullable|numeric|min:0',
            'price_per_kg' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ];
    }
}
