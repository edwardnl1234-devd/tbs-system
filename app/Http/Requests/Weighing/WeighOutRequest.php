<?php

namespace App\Http\Requests\Weighing;

use Illuminate\Foundation\Http\FormRequest;

class WeighOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tara_weight' => 'required|numeric|min:0',
        ];
    }
}
