<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sales extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'so_number',
        'product_type',
        'quantity',
        'price_per_kg',
        'total_amount',
        'order_date',
        'delivery_date',
        'truck_plate',
        'driver_name',
        'status',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'price_per_kg' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'order_date' => 'date',
        'delivery_date' => 'date',
        'product_type' => 'string',
        'status' => 'string',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function details()
    {
        return $this->hasMany(SalesDetail::class, 'sales_id');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('order_date', today());
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByProduct($query, $productType)
    {
        return $query->where('product_type', $productType);
    }
}
