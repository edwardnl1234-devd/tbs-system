<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_cpo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('production_id')->nullable()->constrained('productions')->nullOnDelete();
            $table->decimal('quantity', 10, 2);
            $table->enum('quality_grade', ['premium', 'standard', 'low', null])->nullable();
            $table->string('tank_number', 20)->nullable();
            $table->decimal('tank_capacity', 10, 2)->nullable();
            $table->enum('stock_type', ['production', 'persediaan', 'reserved'])->default('production');
            $table->enum('movement_type', ['in', 'out', 'adjustment']);
            $table->string('reference_number', 50)->nullable();
            $table->date('stock_date');
            $table->date('expiry_date')->nullable();
            $table->enum('status', ['available', 'reserved', 'sold', 'transit'])->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['stock_date', 'status']);
            $table->index('tank_number');
            $table->index('movement_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_cpo');
    }
};
