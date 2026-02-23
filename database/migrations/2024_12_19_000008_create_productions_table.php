<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('stock_tbs_id')->nullable()->constrained('stock_tbs')->nullOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('tbs_input_weight', 10, 2);
            $table->decimal('cpo_output', 10, 2)->default(0);
            $table->decimal('kernel_output', 10, 2)->default(0);
            $table->decimal('shell_output', 10, 2)->default(0);
            $table->decimal('empty_bunch_output', 10, 2)->default(0);
            $table->decimal('cpo_extraction_rate', 5, 2)->nullable();
            $table->decimal('kernel_extraction_rate', 5, 2)->nullable();
            $table->date('production_date');
            $table->string('shift', 20)->nullable();
            $table->string('batch_number', 50)->nullable();
            $table->string('status', 20)->default('processing');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['production_date', 'status']);
            $table->index('batch_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productions');
    }
};
