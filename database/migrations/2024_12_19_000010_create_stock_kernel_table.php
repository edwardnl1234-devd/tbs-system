<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_kernel', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('production_id')->nullable()->constrained('productions')->nullOnDelete();
            $table->decimal('quantity', 10, 2);
            $table->string('quality_grade', 20)->nullable();
            $table->string('location', 100)->nullable();
            $table->string('status', 20)->default('available');
            $table->date('stock_date');
            $table->timestamps();
            
            $table->index(['stock_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_kernel');
    }
};
