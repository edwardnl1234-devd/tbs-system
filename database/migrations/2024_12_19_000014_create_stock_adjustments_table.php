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
            $table->enum('product_type', ['CPO', 'Kernel', 'Shell', 'TBS']);
            $table->decimal('system_stock', 10, 2);
            $table->decimal('physical_stock', 10, 2);
            $table->decimal('difference', 10, 2);
            $table->enum('adjustment_type', ['plus', 'minus', 'correction']);
            $table->text('reason')->nullable();
            $table->foreignId('adjusted_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('adjustment_date');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
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
