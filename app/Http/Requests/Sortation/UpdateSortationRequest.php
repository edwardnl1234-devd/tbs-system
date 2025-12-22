<?php

namespace App\Http\Requests\Sortation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSortationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'good_quality_weight' => 'nullable|numeric|min:0',
            'medium_quality_weight' => 'nullable|numeric|min:0',
            'poor_quality_weight' => 'nullable|numeric|min:0',
            'reject_weight' => 'nullable|numeric|min:0',
            'assistant_deduction' => 'nullable|numeric|min:0',
            'deduction_reason' => 'nullable|string',
            'final_accepted_weight' => 'sometimes|numeric|min:0',
            'mandor_score' => 'nullable|integer|min:0|max:100',
            'operator_discipline_score' => 'nullable|integer|min:0|max:100',
            'notes' => 'nullable|string',
        ];
    }
}
