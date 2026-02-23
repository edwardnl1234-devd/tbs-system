<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Note: role is now VARCHAR, updates are data-only
     */
    public function up(): void
    {
        // Map old roles to new roles (role column is now VARCHAR)
        DB::table('users')->where('role', 'owner')->update(['role' => 'admin']);
        DB::table('users')->where('role', 'supervisor')->update(['role' => 'manager']);
        DB::table('users')->where('role', 'operator')->update(['role' => 'operator_timbangan']);
        DB::table('users')->where('role', 'staff')->update(['role' => 'operator_timbangan']);
        DB::table('users')->where('role', 'mandor')->update(['role' => 'operator_timbangan']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert role mappings
        DB::table('users')->where('role', 'operator_timbangan')->update(['role' => 'staff']);
    }
};
