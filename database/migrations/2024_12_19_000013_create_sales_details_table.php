<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('sales_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('stock_cpo_id')->nullable()->constrained('stock_cpo')->nullOnDelete();
            $table->foreignId('stock_kernel_id')->nullable()->constrained('stock_kernel')->nullOnDelete();
            $table->foreignId('stock_shell_id')->nullable()->constrained('stock_shell')->nullOnDelete();
            $table->decimal('quantity_sold', 10, 2);
            $table->timestamps();
            
            $table->index('sales_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_details');
    }
};
