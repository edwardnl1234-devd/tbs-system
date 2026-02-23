<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Final Roles Structure:
     * - admin: Full access - can view, add, edit, delete everything
     * - manager: View only - cannot add, edit or delete
     * - mandor: Operational role - can add weighing, sortation, queue
     * - accounting: Financial role - can view sales/purchases/stock/customer/supplier/tbs-prices
     *               can add sales and stock purchases, cannot delete anything
     * - operator_timbangan: Weighing operator - can add weighing data
     */
    public function up(): void
    {
        // Step 1: Expand enum to include all values (old + new)
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM(
            'owner', 'admin', 'manager', 'accounting', 'finance', 
            'supervisor', 'operator', 'staff', 'mandor', 'operator_timbangan'
        ) DEFAULT 'operator_timbangan'");
        
        // Step 2: Map old roles to new roles
        // owner -> admin (owner tidak ada lagi)
        DB::table('users')->where('role', 'owner')->update(['role' => 'admin']);
        
        // finance -> accounting (digabung)
        DB::table('users')->where('role', 'finance')->update(['role' => 'accounting']);
        
        // supervisor -> manager
        DB::table('users')->where('role', 'supervisor')->update(['role' => 'manager']);
        
        // operator -> operator_timbangan
        DB::table('users')->where('role', 'operator')->update(['role' => 'operator_timbangan']);
        
        // staff -> operator_timbangan
        DB::table('users')->where('role', 'staff')->update(['role' => 'operator_timbangan']);
        
        // mandor stays as mandor (no change needed)
        
        // Step 3: Restrict to only new roles
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM(
            'admin', 'manager', 'mandor', 'accounting', 'operator_timbangan'
        ) DEFAULT 'operator_timbangan'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to previous enum structure
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM(
            'owner', 'admin', 'manager', 'accounting', 'finance', 
            'supervisor', 'operator', 'staff', 'mandor', 'operator_timbangan'
        ) DEFAULT 'operator_timbangan'");
    }
};
