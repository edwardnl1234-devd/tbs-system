<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TbsPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'price_date',
        'price_per_kg',
        'price_type',
        'quality_grade',
        'set_by',
        'notes',
    ];

    protected $casts = [
        'price_per_kg' => 'decimal:2',
        'price_type' => 'string',
        'quality_grade' => 'string',
        'price_date' => 'date',
    ];

    public function setBy()
    {
        return $this->belongsTo(User::class, 'set_by');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('price_date', today());
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('price_date', $date);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('price_date', 'desc');
    }

    public static function getLatestPrice($priceType = 'H-1', $qualityGrade = null)
    {
        return static::where('price_type', $priceType)
            ->when($qualityGrade, fn($q) => $q->where('quality_grade', $qualityGrade))
            ->orderBy('price_date', 'desc')
            ->first();
    }
}
