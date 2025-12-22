<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesDetail extends Model
{
    use HasFactory;

    protected $table = 'sales_details';

    protected $fillable = [
        'sales_id',
        'stock_cpo_id',
        'stock_kernel_id',
        'stock_shell_id',
        'quantity_sold',
    ];

    protected $casts = [
        'quantity_sold' => 'decimal:2',
    ];

    public function sales()
    {
        return $this->belongsTo(Sales::class, 'sales_id');
    }

    public function stockCpo()
    {
        return $this->belongsTo(StockCpo::class);
    }

    public function stockKernel()
    {
        return $this->belongsTo(StockKernel::class);
    }

    public function stockShell()
    {
        return $this->belongsTo(StockShell::class);
    }
}
