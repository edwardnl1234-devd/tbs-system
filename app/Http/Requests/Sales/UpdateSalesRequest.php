<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSalesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'sometimes|exists:customers,id',
            'product_type' => 'sometimes|in:CPO,Kernel,Shell,Empty_Bunch',
            'quantity' => 'sometimes|numeric|min:0.01',
            'price_per_kg' => 'sometimes|numeric|min:0',
            'delivery_date' => 'nullable|date',
            'truck_plate' => 'nullable|string|max:20',
            'driver_name' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ];
    }
}
