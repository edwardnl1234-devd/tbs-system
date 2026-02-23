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
        
        // Modify stock_type enum to include 'purchase' 
        DB::statement("ALTER TABLE stock_cpo MODIFY COLUMN stock_type ENUM('production', 'persediaan', 'reserved', 'purchase') DEFAULT 'production'");

        // Add supplier_id and purchase_price to stock_kernel table
        Schema::table('stock_kernel', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('production_id')->constrained('suppliers')->nullOnDelete();
            $table->decimal('purchase_price', 15, 2)->nullable()->after('quantity');
            $table->enum('stock_type', ['production', 'purchase'])->default('production')->after('purchase_price');
        });

        // Add supplier_id and purchase_price to stock_shell table
        Schema::table('stock_shell', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('production_id')->constrained('suppliers')->nullOnDelete();
            $table->decimal('purchase_price', 15, 2)->nullable()->after('quantity');
            $table->enum('stock_type', ['production', 'purchase'])->default('production')->after('purchase_price');
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
        
        DB::statement("ALTER TABLE stock_cpo MODIFY COLUMN stock_type ENUM('production', 'persediaan', 'reserved') DEFAULT 'production'");

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
