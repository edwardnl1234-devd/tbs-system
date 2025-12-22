<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'sometimes|string|max:50|unique:customers,code,' . $this->route('customer'),
            'name' => 'sometimes|string|max:200',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'product_types' => 'nullable|array',
            'product_types.*' => 'string',
            'status' => 'sometimes|in:active,inactive',
        ];
    }
}
