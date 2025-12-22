<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sortations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('weighing_id')->constrained('weighings')->cascadeOnDelete();
            $table->foreignId('mandor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('good_quality_weight', 10, 2)->default(0);
            $table->decimal('medium_quality_weight', 10, 2)->default(0);
            $table->decimal('poor_quality_weight', 10, 2)->default(0);
            $table->decimal('reject_weight', 10, 2)->default(0);
            $table->decimal('assistant_deduction', 10, 2)->default(0);
            $table->text('deduction_reason')->nullable();
            $table->decimal('final_accepted_weight', 10, 2);
            $table->tinyInteger('mandor_score')->nullable();
            $table->tinyInteger('operator_discipline_score')->nullable();
            $table->dateTime('sortation_time')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('sortation_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sortations');
    }
};
