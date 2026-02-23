<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queues', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('truck_id')->constrained('trucks')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('queue_number', 20)->unique();
            $table->string('supplier_type', 20)->default('umum');
            $table->tinyInteger('bank')->nullable();
            $table->dateTime('arrival_time');
            $table->dateTime('call_time')->nullable();
            $table->dateTime('estimated_call_time')->nullable();
            $table->string('status', 20)->default('waiting');
            $table->tinyInteger('priority')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'arrival_time']);
            $table->index('bank');
            $table->index('supplier_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queues');
    }
};
