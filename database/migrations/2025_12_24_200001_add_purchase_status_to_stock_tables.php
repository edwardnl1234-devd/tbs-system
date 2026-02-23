<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update stock_cpo status enum to include purchase statuses
        Schema::table('stock_cpo', function (Blueprint $table) {
            $table->enum('purchase_status', ['pending', 'in_process', 'done'])->default('pending')->after('status');
        });

        // Update stock_kernel status enum to include purchase statuses
        Schema::table('stock_kernel', function (Blueprint $table) {
            $table->enum('purchase_status', ['pending', 'in_process', 'done'])->default('pending')->after('status');
        });

        // Update stock_shell status enum to include purchase statuses
        Schema::table('stock_shell', function (Blueprint $table) {
            $table->enum('purchase_status', ['pending', 'in_process', 'done'])->default('pending')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('stock_cpo', function (Blueprint $table) {
            $table->dropColumn('purchase_status');
        });

        Schema::table('stock_kernel', function (Blueprint $table) {
            $table->dropColumn('purchase_status');
        });

        Schema::table('stock_shell', function (Blueprint $table) {
            $table->dropColumn('purchase_status');
        });
    }
};
