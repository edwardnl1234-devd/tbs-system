<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sortation extends Model
{
    use HasFactory;

    protected $fillable = [
        'weighing_id',
        'mandor_id',
        'good_quality_weight',
        'medium_quality_weight',
        'poor_quality_weight',
        'reject_weight',
        'assistant_deduction',
        'deduction_reason',
        'final_accepted_weight',
        'mandor_score',
        'operator_discipline_score',
        'sortation_time',
        'notes',
    ];

    protected $casts = [
        'good_quality_weight' => 'decimal:2',
        'medium_quality_weight' => 'decimal:2',
        'poor_quality_weight' => 'decimal:2',
        'reject_weight' => 'decimal:2',
        'assistant_deduction' => 'decimal:2',
        'final_accepted_weight' => 'decimal:2',
        'sortation_time' => 'datetime',
        'mandor_score' => 'integer',
        'operator_discipline_score' => 'integer',
    ];

    public function weighing()
    {
        return $this->belongsTo(Weighing::class);
    }

    public function mandor()
    {
        return $this->belongsTo(User::class, 'mandor_id');
    }

    public function stockTbs()
    {
        return $this->hasOne(StockTbs::class);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('sortation_time', today());
    }
}
