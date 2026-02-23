<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TbsPrice extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'effective_date',
        'price_per_kg',
        'supplier_type',
        'notes',
    ];

    protected $casts = [
        'price_per_kg' => 'decimal:2',
        'supplier_type' => 'string',
        'effective_date' => 'date',
    ];

    public function scopeToday($query)
    {
        return $query->whereDate('effective_date', today());
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('effective_date', $date);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('effective_date', 'desc');
    }

    /**
     * Get price for a specific date and supplier type
     * If no price found for the date, get the latest available price
     *
     * @param string $supplierType inti|plasma|umum
     * @param \Carbon\Carbon|string|null $date
     * @return float|null
     */
    public static function getPriceForDate(string $supplierType, $date = null): ?float
    {
        $date = $date ? \Carbon\Carbon::parse($date) : today();

        // Try to get exact date price first
        $price = static::where('supplier_type', $supplierType)
            ->whereDate('effective_date', $date)
            ->first();

        // If not found, get the latest price before or on this date
        if (!$price) {
            $price = static::where('supplier_type', $supplierType)
                ->whereDate('effective_date', '<=', $date)
                ->orderBy('effective_date', 'desc')
                ->first();
        }

        // If still not found, get the most recent price regardless of date
        if (!$price) {
            $price = static::where('supplier_type', $supplierType)
                ->orderBy('effective_date', 'desc')
                ->first();
        }

        return $price?->price_per_kg;
    }

    /**
     * Get today's prices for all supplier types
     *
     * @return array
     */
    public static function getTodayPrices(): array
    {
        $types = ['inti', 'plasma', 'umum'];
        $prices = [];

        foreach ($types as $type) {
            $prices[$type] = static::getPriceForDate($type);
        }

        return $prices;
    }

    /**
     * Check if price exists for today
     *
     * @param string|null $supplierType
     * @return bool
     */
    public static function hasTodayPrice(?string $supplierType = null): bool
    {
        $query = static::whereDate('effective_date', today());

        if ($supplierType) {
            $query->where('supplier_type', $supplierType);
        }

        return $query->exists();
    }
}
