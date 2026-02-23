<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'nullable|string|max:50|unique:customers,code',
            'name' => 'required|string|max:200',
            'contact_person' => 'nullable|string|max:100',
            'company' => 'nullable|string|max:100', // Frontend sends this, map to contact_person
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'product_types' => 'nullable|array',
            'product_types.*' => 'string',
            'status' => 'nullable|in:active,inactive',
            'is_active' => 'nullable|boolean',
        ];
    }
}
