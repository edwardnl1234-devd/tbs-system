<?php

namespace App\Http\Requests\Queue;

use Illuminate\Foundation\Http\FormRequest;

class StoreQueueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'truck_id' => 'required|exists:trucks,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'supplier_type' => 'nullable|in:inti,plasma,umum',
            'notes' => 'nullable|string',
        ];
    }
}
