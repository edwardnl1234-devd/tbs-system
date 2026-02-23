<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbs_prices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('effective_date');
            $table->string('supplier_type', 20)->default('umum');
            $table->decimal('price_per_kg', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['effective_date', 'supplier_type']);
            $table->index('effective_date');
            $table->index('supplier_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbs_prices');
    }
};
