<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockShell extends Model
{
    use HasFactory;

    protected $table = 'stock_shell';

    protected $fillable = [
        'production_id',
        'quantity',
        'location',
        'status',
        'stock_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'status' => 'string',
        'stock_date' => 'date',
    ];

    public function production()
    {
        return $this->belongsTo(Production::class);
    }

    public function salesDetails()
    {
        return $this->hasMany(SalesDetail::class, 'stock_shell_id');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }
}
