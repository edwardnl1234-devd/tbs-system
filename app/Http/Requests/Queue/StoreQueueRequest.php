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
            'bank' => 'nullable|integer|in:1,2',
            'priority' => 'nullable|integer|min:0|max:10',
            'notes' => 'nullable|string',
        ];
    }
}
