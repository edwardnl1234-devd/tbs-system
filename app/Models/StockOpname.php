<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockOpname extends Model
{
    use HasFactory;

    protected $fillable = [
        'opname_date',
        'product_type',
        'location',
        'physical_quantity',
        'system_quantity',
        'variance',
        'variance_percentage',
        'counted_by',
        'verified_by',
        'remarks',
        'status',
    ];

    protected $casts = [
        'physical_quantity' => 'decimal:2',
        'system_quantity' => 'decimal:2',
        'variance' => 'decimal:2',
        'variance_percentage' => 'decimal:2',
        'product_type' => 'string',
        'status' => 'string',
        'opname_date' => 'date',
    ];

    public function countedBy()
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('opname_date', $date);
    }

    public function scopeByProduct($query, $productType)
    {
        return $query->where('product_type', $productType);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('opname_date', 'desc');
    }
}
