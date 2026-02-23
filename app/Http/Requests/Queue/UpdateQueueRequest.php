<?php

namespace App\Http\Requests\Queue;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQueueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => 'nullable|exists:suppliers,id',
            'supplier_type' => 'nullable|in:inti,plasma,umum',
            'call_time' => 'nullable|date',
            'estimated_call_time' => 'nullable|date',
            'notes' => 'nullable|string',
        ];
    }
}
