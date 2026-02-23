<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Weighing extends Model
{
    use HasFactory, LogsActivity;

    /**
     * Get identifier for activity log
     */
    protected function getActivityIdentifier(): string
    {
        return "Tiket '{$this->ticket_number}'";
    }

    protected $fillable = [
        'queue_id',
        'operator_id',
        'ticket_number',
        'product_type',
        'bruto_weight',
        'tara_weight',
        'netto_weight',
        'cpo_weight',
        'kernel_weight',
        'cangkang_weight',
        'fiber_weight',
        'jangkos_weight',
        'price_per_kg',
        'total_price',
        'weigh_in_time',
        'weigh_out_time',
        'status',
        'notes',
    ];

    protected $casts = [
        'bruto_weight' => 'decimal:2',
        'tara_weight' => 'decimal:2',
        'netto_weight' => 'decimal:2',
        'cpo_weight' => 'decimal:2',
        'kernel_weight' => 'decimal:2',
        'cangkang_weight' => 'decimal:2',
        'fiber_weight' => 'decimal:2',
        'jangkos_weight' => 'decimal:2',
        'price_per_kg' => 'decimal:2',
        'total_price' => 'decimal:2',
        'weigh_in_time' => 'datetime',
        'weigh_out_time' => 'datetime',
        'status' => 'string',
        'product_type' => 'string',
    ];

    public function queue()
    {
        return $this->belongsTo(Queue::class);
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function sortation()
    {
        return $this->hasOne(Sortation::class);
    }

    public function stockTbs()
    {
        return $this->hasOne(StockTbs::class);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('weigh_in_time', today());
    }

    public function scopePending($query)
    {
        return $query->where('status', '!=', 'completed');
    }

    public function scopeByQueue($query, $queueId)
    {
        return $query->where('queue_id', $queueId);
    }
}
