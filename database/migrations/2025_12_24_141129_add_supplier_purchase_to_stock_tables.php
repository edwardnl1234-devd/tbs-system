<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add supplier_id and purchase_price to stock_cpo table
        Schema::table('stock_cpo', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('production_id')->constrained('suppliers')->nullOnDelete();
            $table->decimal('purchase_price', 15, 2)->nullable()->after('quantity');
        });
        
        // stock_type is now VARCHAR - 'purchase' value is supported without schema change

        // Add supplier_id and purchase_price to stock_kernel table
        Schema::table('stock_kernel', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('production_id')->constrained('suppliers')->nullOnDelete();
            $table->decimal('purchase_price', 15, 2)->nullable()->after('quantity');
            $table->string('stock_type', 20)->default('production')->after('purchase_price');
        });

        // Add supplier_id and purchase_price to stock_shell table
        Schema::table('stock_shell', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('production_id')->constrained('suppliers')->nullOnDelete();
            $table->decimal('purchase_price', 15, 2)->nullable()->after('quantity');
            $table->string('stock_type', 20)->default('production')->after('purchase_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_cpo', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['supplier_id', 'purchase_price']);
        });
        
        // stock_type is VARCHAR - no enum revert needed

        Schema::table('stock_kernel', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['supplier_id', 'purchase_price', 'stock_type']);
        });

        Schema::table('stock_shell', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['supplier_id', 'purchase_price', 'stock_type']);
        });
    }
};
