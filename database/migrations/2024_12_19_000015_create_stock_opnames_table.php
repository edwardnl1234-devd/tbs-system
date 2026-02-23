<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('opname_date');
            $table->string('product_type', 30);
            $table->string('location', 100)->nullable();
            $table->decimal('physical_quantity', 10, 2);
            $table->decimal('system_quantity', 10, 2);
            $table->decimal('variance', 10, 2);
            $table->decimal('variance_percentage', 5, 2)->nullable();
            $table->foreignId('counted_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamps();
            
            $table->index(['opname_date', 'product_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opnames');
    }
};
