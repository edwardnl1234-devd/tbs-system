<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Note: status is now VARCHAR, 'reserved' value is supported without schema change
     */
    public function up(): void
    {
        // No schema change needed - status column is now VARCHAR
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
