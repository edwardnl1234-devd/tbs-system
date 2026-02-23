<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Note: role is now VARCHAR, no enum modification needed
     */
    public function up(): void
    {
        // Role 'finance' is now supported via string column - no schema change needed
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
