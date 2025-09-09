<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add deleted_at column to asset_warranties table
        if (Schema::hasTable('asset_warranties')) {
            Schema::table('asset_warranties', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add deleted_at column to asset_maintenances table (only if it exists)
        if (Schema::hasTable('asset_maintenances')) {
            Schema::table('asset_maintenances', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add deleted_at column to asset_depreciations table
        if (Schema::hasTable('asset_depreciations')) {
            Schema::table('asset_depreciations', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('asset_warranties')) {
            Schema::table('asset_warranties', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('asset_maintenances')) {
            Schema::table('asset_maintenances', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('asset_depreciations')) {
            Schema::table('asset_depreciations', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
