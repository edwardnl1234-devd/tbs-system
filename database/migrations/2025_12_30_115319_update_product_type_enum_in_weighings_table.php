<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Note: product_type is now VARCHAR, no enum modification needed
     */
    public function up(): void
    {
        // No schema change needed - product_type column is now VARCHAR
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
