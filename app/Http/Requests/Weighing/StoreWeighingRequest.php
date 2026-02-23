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
            'product_type' => 'nullable|in:TBS,CPO,Kernel,Cangkang,Fiber,Jangkos',
            'bruto_weight' => 'nullable|numeric|min:0',
            'tara_weight' => 'nullable|numeric|min:0',
            'netto_weight' => 'nullable|numeric|min:0',
            'price_per_kg' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            // Derivative weights
            'cpo_weight' => 'nullable|numeric|min:0',
            'kernel_weight' => 'nullable|numeric|min:0',
            'cangkang_weight' => 'nullable|numeric|min:0',
            'fiber_weight' => 'nullable|numeric|min:0',
            'jangkos_weight' => 'nullable|numeric|min:0',
        ];
    }
}
