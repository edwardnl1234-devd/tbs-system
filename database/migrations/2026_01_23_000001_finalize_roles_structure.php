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
        // Map old roles to new roles (role column is now VARCHAR)
        // owner -> admin
        DB::table('users')->where('role', 'owner')->update(['role' => 'admin']);
        
        // finance -> accounting
        DB::table('users')->where('role', 'finance')->update(['role' => 'accounting']);
        
        // supervisor -> manager
        DB::table('users')->where('role', 'supervisor')->update(['role' => 'manager']);
        
        // operator -> operator_timbangan
        DB::table('users')->where('role', 'operator')->update(['role' => 'operator_timbangan']);
        
        // staff -> operator_timbangan
        DB::table('users')->where('role', 'staff')->update(['role' => 'operator_timbangan']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert role mappings
        DB::table('users')->where('role', 'operator_timbangan')->update(['role' => 'staff']);
        DB::table('users')->where('role', 'accounting')->update(['role' => 'finance']);
    }
};
