<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockOpnameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'physical_quantity' => 'sometimes|numeric|min:0',
            'location' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
        ];
    }
}
