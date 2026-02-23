<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockCpo extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'stock_cpo';

    /**
     * Get identifier for activity log
     */
    protected function getActivityIdentifier(): string
    {
        $source = $this->supplier_id ? 'Pembelian' : 'Produksi';
        $tank = $this->tank_number ? " Tank: {$this->tank_number}" : '';
        return "#{$this->id} ({$source}{$tank})";
    }

    protected $fillable = [
        'production_id',
        'supplier_id',
        'quantity',
        'purchase_price',
        'quality_grade',
        'tank_number',
        'tank_capacity',
        'stock_type',
        'movement_type',
        'reference_number',
        'stock_date',
        'expiry_date',
        'status',
        'purchase_status',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'tank_capacity' => 'decimal:2',
        'quality_grade' => 'string',
        'stock_type' => 'string',
        'movement_type' => 'string',
        'status' => 'string',
        'stock_date' => 'date',
        'expiry_date' => 'date',
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
        return $this->hasMany(SalesDetail::class, 'stock_cpo_id');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeByTank($query, $tankNumber)
    {
        return $query->where('tank_number', $tankNumber);
    }

    public function scopeMovementIn($query)
    {
        return $query->where('movement_type', 'in');
    }

    public function scopeMovementOut($query)
    {
        return $query->where('movement_type', 'out');
    }
}
