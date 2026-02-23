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
        // Add 'reserved' to stock_kernel status enum
        DB::statement("ALTER TABLE stock_kernel MODIFY COLUMN status ENUM('available', 'sold', 'transit', 'reserved') DEFAULT 'available'");
        
        // Add 'reserved' to stock_shell status enum
        DB::statement("ALTER TABLE stock_shell MODIFY COLUMN status ENUM('available', 'sold', 'reserved') DEFAULT 'available'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert stock_kernel status enum
        DB::statement("ALTER TABLE stock_kernel MODIFY COLUMN status ENUM('available', 'sold', 'transit') DEFAULT 'available'");
        
        // Revert stock_shell status enum
        DB::statement("ALTER TABLE stock_shell MODIFY COLUMN status ENUM('available', 'sold') DEFAULT 'available'");
    }
};
