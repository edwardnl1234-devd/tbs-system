<?php

namespace App\Http\Requests\Weighing;

use Illuminate\Foundation\Http\FormRequest;

class WeighInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bruto_weight' => 'required|numeric|min:0',
        ];
    }
}
