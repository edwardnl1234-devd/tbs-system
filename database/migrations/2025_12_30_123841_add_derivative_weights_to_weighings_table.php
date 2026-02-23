<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add columns for all palm oil derivative products weights
     */
    public function up(): void
    {
        Schema::table('weighings', function (Blueprint $table) {
            // Palm oil derivative weights (hasil turunan TBS)
            $table->decimal('cpo_weight', 12, 2)->nullable()->after('netto_weight')->comment('CPO weight in kg');
            $table->decimal('kernel_weight', 12, 2)->nullable()->after('cpo_weight')->comment('Kernel weight in kg');
            $table->decimal('cangkang_weight', 12, 2)->nullable()->after('kernel_weight')->comment('Cangkang/Shell weight in kg');
            $table->decimal('fiber_weight', 12, 2)->nullable()->after('cangkang_weight')->comment('Fiber weight in kg');
            $table->decimal('jangkos_weight', 12, 2)->nullable()->after('fiber_weight')->comment('Jangkos/Empty Bunch weight in kg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weighings', function (Blueprint $table) {
            $table->dropColumn([
                'cpo_weight',
                'kernel_weight',
                'cangkang_weight',
                'fiber_weight',
                'jangkos_weight',
            ]);
        });
    }
};
