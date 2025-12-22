<?php

namespace App\Http\Requests\Truck;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTruckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plate_number' => ['sometimes', 'string', 'max:20', Rule::unique('trucks')->ignore($this->route('truck'))],
            'driver_name' => 'nullable|string|max:100',
            'driver_phone' => 'nullable|string|max:20',
            'type' => 'nullable|string|max:50',
            'capacity' => 'nullable|numeric|min:0',
            'status' => 'sometimes|in:active,inactive',
        ];
    }
}
