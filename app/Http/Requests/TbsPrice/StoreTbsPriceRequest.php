<?php

namespace App\Http\Requests\TbsPrice;

use Illuminate\Foundation\Http\FormRequest;

class StoreTbsPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'price_date' => 'required|date',
            'price_per_kg' => 'required|numeric|min:0',
            'price_type' => 'required|in:H-1,H-2,spot',
            'quality_grade' => 'nullable|in:A,B,C',
            'notes' => 'nullable|string',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Check for duplicate price entry
            $exists = \App\Models\TbsPrice::where('price_date', $this->price_date)
                ->where('price_type', $this->price_type)
                ->where('quality_grade', $this->quality_grade)
                ->exists();

            if ($exists) {
                $validator->errors()->add('price_date', 'A price entry with this date, type, and grade already exists.');
            }
        });
    }
}
