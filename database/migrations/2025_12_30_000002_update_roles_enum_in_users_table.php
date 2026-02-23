<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * New Roles:
     * - admin: Can view and edit everything
     * - manager: Can view only, cannot add or edit
     * - accounting: Can view purchases/sales, can add but cannot edit
     * - finance: Can view purchases/sales, can add but cannot edit  
     * - operator_timbangan: Can add weighing data, cannot edit
     */
    public function up(): void
    {
        // First, expand the enum to include all old AND new values
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('owner', 'admin', 'manager', 'accounting', 'finance', 'supervisor', 'operator', 'staff', 'mandor', 'operator_timbangan') DEFAULT 'operator_timbangan'");
        
        // Map old roles to new roles
        DB::table('users')->where('role', 'owner')->update(['role' => 'admin']);
        DB::table('users')->where('role', 'supervisor')->update(['role' => 'manager']);
        DB::table('users')->where('role', 'operator')->update(['role' => 'operator_timbangan']);
        DB::table('users')->where('role', 'staff')->update(['role' => 'operator_timbangan']);
        DB::table('users')->where('role', 'mandor')->update(['role' => 'operator_timbangan']);
        
        // Now restrict to only new roles
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'manager', 'accounting', 'finance', 'operator_timbangan') DEFAULT 'operator_timbangan'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to previous enum
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('owner', 'admin', 'manager', 'finance', 'supervisor', 'operator', 'staff', 'mandor') DEFAULT 'staff'");
    }
};
