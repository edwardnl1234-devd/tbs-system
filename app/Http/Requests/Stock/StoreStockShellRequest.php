<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockShellRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'production_id' => 'nullable|exists:productions,id',
            'quantity' => 'required|numeric|min:0',
            'location' => 'nullable|string|max:100',
            'status' => 'nullable|in:available,sold',
            'stock_date' => 'required|date',
        ];
    }
}
