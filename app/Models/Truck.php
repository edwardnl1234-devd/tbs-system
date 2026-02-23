<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Truck extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'plate_number',
        'driver_name',
        'driver_phone',
        'type',
        'capacity',
        'status',
    ];

    protected $casts = [
        'type' => 'string',
        'capacity' => 'decimal:2',
        'status' => 'string',
    ];

    protected $appends = ['is_active'];

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function queues()
    {
        return $this->hasMany(Queue::class);
    }

    public function weighings()
    {
        return $this->hasMany(Weighing::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}
