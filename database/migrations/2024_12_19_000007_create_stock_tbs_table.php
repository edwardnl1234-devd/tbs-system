<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_tbs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('weighing_id')->nullable()->constrained('weighings')->nullOnDelete();
            $table->foreignId('sortation_id')->nullable()->constrained('sortations')->nullOnDelete();
            $table->decimal('quantity', 10, 2);
            $table->enum('quality_grade', ['A', 'B', 'C', null])->nullable();
            $table->enum('status', ['ready', 'processing', 'processed'])->default('ready');
            $table->string('location', 100)->nullable();
            $table->date('received_date');
            $table->date('processed_date')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'received_date']);
            $table->index('quality_grade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_tbs');
    }
};
