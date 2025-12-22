<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'product_type' => 'required|in:CPO,Kernel,Shell,Empty_Bunch',
            'quantity' => 'required|numeric|min:0.01',
            'price_per_kg' => 'required|numeric|min:0',
            'order_date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:order_date',
            'truck_plate' => 'nullable|string|max:20',
            'driver_name' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ];
    }
}
