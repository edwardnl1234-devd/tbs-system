<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('product_type', 30);
            $table->decimal('system_stock', 10, 2);
            $table->decimal('physical_stock', 10, 2);
            $table->decimal('difference', 10, 2);
            $table->string('adjustment_type', 20);
            $table->text('reason')->nullable();
            $table->foreignId('adjusted_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('adjustment_date');
            $table->string('status', 20)->default('pending');
            $table->timestamps();
            
            $table->index(['adjustment_date', 'status']);
            $table->index('product_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
