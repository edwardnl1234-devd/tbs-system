<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $supplierId = $this->route('supplier');
        
        return [
            'code' => 'sometimes|string|max:50|unique:suppliers,code,' . $supplierId,
            'name' => 'sometimes|string|max:200',
            'type' => 'sometimes|in:inti,plasma,umum',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive',
        ];
    }
}
