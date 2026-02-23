<?php

namespace App\Http\Requests\Truck;

use Illuminate\Foundation\Http\FormRequest;

class StoreTruckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Convert empty strings to null for numeric fields
        if ($this->capacity === '' || $this->capacity === null) {
            $this->merge(['capacity' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'plate_number' => 'required|string|max:20|unique:trucks,plate_number',
            'driver_name' => 'nullable|string|max:100',
            'driver_phone' => 'nullable|string|max:20',
            'type' => 'nullable|string|max:50',
            'capacity' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:active,inactive',
            'is_active' => 'nullable|boolean',
        ];
    }
}
