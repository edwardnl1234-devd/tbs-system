<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockShell extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'stock_shell';

    /**
     * Get identifier for activity log
     */
    protected function getActivityIdentifier(): string
    {
        $source = $this->supplier_id ? 'Pembelian' : 'Produksi';
        return "#{$this->id} ({$source})";
    }

    protected $fillable = [
        'production_id',
        'supplier_id',
        'quantity',
        'purchase_price',
        'location',
        'stock_type',
        'status',
        'purchase_status',
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

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
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
