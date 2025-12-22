<?php

namespace App\Http\Requests\Production;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tbs_input_weight' => 'sometimes|numeric|min:0',
            'cpo_output' => 'nullable|numeric|min:0',
            'kernel_output' => 'nullable|numeric|min:0',
            'shell_output' => 'nullable|numeric|min:0',
            'empty_bunch_output' => 'nullable|numeric|min:0',
            'production_date' => 'sometimes|date',
            'shift' => 'nullable|in:pagi,siang,malam',
            'batch_number' => 'nullable|string|max:50',
            'status' => 'sometimes|in:processing,completed,quality_check',
            'notes' => 'nullable|string',
        ];
    }
}
