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
        // Update ENUM to include all product types
        DB::statement("ALTER TABLE weighings MODIFY COLUMN product_type ENUM('TBS', 'CPO', 'Kernel', 'Shell', 'Inti', 'Cangkang') DEFAULT 'TBS'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original ENUM values
        DB::statement("ALTER TABLE weighings MODIFY COLUMN product_type ENUM('TBS', 'Inti', 'Cangkang') DEFAULT 'TBS'");
    }
};
