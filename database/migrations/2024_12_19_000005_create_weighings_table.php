<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weighings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('queue_id')->constrained('queues')->cascadeOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ticket_number', 50)->unique();
            $table->decimal('bruto_weight', 10, 2)->nullable();
            $table->decimal('tara_weight', 10, 2)->nullable();
            $table->decimal('netto_weight', 10, 2)->nullable();
            $table->decimal('price_per_kg', 10, 2)->nullable();
            $table->decimal('total_price', 15, 2)->nullable();
            $table->dateTime('weigh_in_time')->nullable();
            $table->dateTime('weigh_out_time')->nullable();
            $table->enum('status', ['pending', 'weigh_in', 'weigh_out', 'completed'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'weigh_in_time']);
            $table->index('ticket_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weighings');
    }
};
