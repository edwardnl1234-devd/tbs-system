<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockTbs extends Model
{
    use HasFactory;

    protected $table = 'stock_tbs';

    protected $fillable = [
        'weighing_id',
        'sortation_id',
        'quantity',
        'quality_grade',
        'status',
        'location',
        'received_date',
        'processed_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'quality_grade' => 'string',
        'status' => 'string',
        'received_date' => 'date',
        'processed_date' => 'date',
    ];

    public function weighing()
    {
        return $this->belongsTo(Weighing::class);
    }

    public function sortation()
    {
        return $this->belongsTo(Sortation::class);
    }

    public function productions()
    {
        return $this->hasMany(Production::class);
    }

    public function scopeReady($query)
    {
        return $query->where('status', 'ready');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeAvailable($query)
    {
        return $query->whereIn('status', ['ready', 'processing']);
    }
}
