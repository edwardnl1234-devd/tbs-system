<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'product_type' => 'required|in:CPO,Kernel,Shell,Empty_Bunch',
            'quantity' => 'required|numeric|min:0.01',
            'price_per_kg' => 'required|numeric|min:0',
            'order_date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:order_date',
            'truck_plate' => 'nullable|string|max:20',
            'driver_name' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Customer wajib dipilih',
            'customer_id.exists' => 'Customer tidak ditemukan',
            'product_type.required' => 'Jenis produk wajib dipilih',
            'product_type.in' => 'Jenis produk tidak valid',
            'quantity.required' => 'Jumlah wajib diisi',
            'quantity.numeric' => 'Jumlah harus berupa angka',
            'quantity.min' => 'Jumlah minimal 0.01 kg',
            'price_per_kg.required' => 'Harga per kg wajib diisi',
            'price_per_kg.numeric' => 'Harga harus berupa angka',
            'order_date.required' => 'Tanggal order wajib diisi',
            'order_date.date' => 'Format tanggal tidak valid',
            'delivery_date.after_or_equal' => 'Tanggal kirim tidak boleh sebelum tanggal order',
        ];
    }
}
