<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Note: role is now VARCHAR, no need to alter enum
     */
    public function up(): void
    {
        // Role 'admin' is now supported via string column - no schema change needed
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
