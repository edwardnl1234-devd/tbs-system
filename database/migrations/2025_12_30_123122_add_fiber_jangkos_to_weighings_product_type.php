<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add Fiber and Jangkos to product_type ENUM for palm oil derivatives
     */
    public function up(): void
    {
        // Update ENUM to include all palm oil derivative products
        // TBS = Tandan Buah Segar (Fresh Fruit Bunch) - raw material
        // CPO = Crude Palm Oil
        // Kernel = Palm Kernel
        // Cangkang = Shell
        // Fiber = Palm Fiber
        // Jangkos = Janjang Kosong (Empty Fruit Bunch)
        DB::statement("ALTER TABLE weighings MODIFY COLUMN product_type ENUM('TBS', 'CPO', 'Kernel', 'Cangkang', 'Fiber', 'Jangkos') DEFAULT 'TBS'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE weighings MODIFY COLUMN product_type ENUM('TBS', 'CPO', 'Kernel', 'Shell', 'Inti', 'Cangkang') DEFAULT 'TBS'");
    }
};
