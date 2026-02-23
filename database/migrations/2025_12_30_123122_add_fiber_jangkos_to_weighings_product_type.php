<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Note: product_type is now VARCHAR, all product types are supported
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
