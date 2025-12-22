<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Production extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_tbs_id',
        'supervisor_id',
        'tbs_input_weight',
        'cpo_output',
        'kernel_output',
        'shell_output',
        'empty_bunch_output',
        'cpo_extraction_rate',
        'kernel_extraction_rate',
        'production_date',
        'shift',
        'batch_number',
        'status',
        'notes',
    ];

    protected $casts = [
        'tbs_input_weight' => 'decimal:2',
        'cpo_output' => 'decimal:2',
        'kernel_output' => 'decimal:2',
        'shell_output' => 'decimal:2',
        'empty_bunch_output' => 'decimal:2',
        'cpo_extraction_rate' => 'decimal:2',
        'kernel_extraction_rate' => 'decimal:2',
        'production_date' => 'date',
        'shift' => 'string',
        'status' => 'string',
    ];

    public function stockTbs()
    {
        return $this->belongsTo(StockTbs::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function stockCpo()
    {
        return $this->hasMany(StockCpo::class);
    }

    public function stockKernel()
    {
        return $this->hasMany(StockKernel::class);
    }

    public function stockShell()
    {
        return $this->hasMany(StockShell::class);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('production_date', today());
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('production_date', $date);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
