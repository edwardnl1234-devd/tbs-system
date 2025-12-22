<?php

namespace App\Http\Requests\Weighing;

use Illuminate\Foundation\Http\FormRequest;

class StoreWeighingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'queue_id' => 'required|exists:queues,id',
            'bruto_weight' => 'nullable|numeric|min:0',
            'price_per_kg' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ];
    }
}
