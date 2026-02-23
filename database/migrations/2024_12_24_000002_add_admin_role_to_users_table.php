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
        // Modify the role enum to include admin
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('owner', 'admin', 'manager', 'supervisor', 'operator', 'staff', 'mandor') DEFAULT 'staff'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('owner', 'manager', 'supervisor', 'operator', 'staff', 'mandor') DEFAULT 'staff'");
    }
};
