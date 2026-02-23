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
            'effective_date' => 'required|date',
            'price_per_kg' => 'required|numeric|min:0',
            'supplier_type' => 'required|in:inti,plasma,umum',
            'notes' => 'nullable|string',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Check for duplicate price entry
            $exists = \App\Models\TbsPrice::where('effective_date', $this->effective_date)
                ->where('supplier_type', $this->supplier_type)
                ->exists();

            if ($exists) {
                $validator->errors()->add('effective_date', 'Harga untuk tanggal dan tipe supplier ini sudah ada.');
            }
        });
    }
}
