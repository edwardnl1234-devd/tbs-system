<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Queue extends Model
{
    use HasFactory;

    protected $fillable = [
        'truck_id',
        'supplier_id',
        'queue_number',
        'supplier_type',
        'arrival_time',
        'call_time',
        'estimated_call_time',
        'status',
        'priority',
        'notes',
    ];

    protected $casts = [
        'arrival_time' => 'datetime',
        'call_time' => 'datetime',
        'estimated_call_time' => 'datetime',
        'status' => 'string',
        'priority' => 'integer',
        'supplier_type' => 'string',
    ];

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function weighing()
    {
        return $this->hasOne(Weighing::class);
    }

    public function scopeWaiting($query)
    {
        return $query->where('status', 'waiting');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['waiting', 'processing']);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('arrival_time', today());
    }

    public function scopeByBank($query, $bank)
    {
        return $query->where('bank', $bank);
    }
}
