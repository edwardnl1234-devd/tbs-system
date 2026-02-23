<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('so_number', 50)->unique();
            $table->string('product_type', 30);
            $table->decimal('quantity', 10, 2);
            $table->decimal('price_per_kg', 10, 2);
            $table->decimal('total_amount', 15, 2);
            $table->date('order_date');
            $table->date('delivery_date')->nullable();
            $table->string('truck_plate', 20)->nullable();
            $table->string('driver_name', 100)->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['order_date', 'status']);
            $table->index('product_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
