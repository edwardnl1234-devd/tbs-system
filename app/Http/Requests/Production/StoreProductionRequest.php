<?php

namespace App\Http\Requests\Production;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stock_tbs_id' => 'nullable|exists:stock_tbs,id',
            'tbs_input_weight' => 'required|numeric|min:0',
            'cpo_output' => 'nullable|numeric|min:0',
            'kernel_output' => 'nullable|numeric|min:0',
            'shell_output' => 'nullable|numeric|min:0',
            'empty_bunch_output' => 'nullable|numeric|min:0',
            'production_date' => 'required|date',
            'shift' => 'nullable|in:pagi,siang,malam',
            'batch_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ];
    }
}
