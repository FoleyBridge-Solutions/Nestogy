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
        // Add deleted_at column to asset_maintenance table
        if (Schema::hasTable('asset_maintenance')) {
            Schema::table('asset_maintenance', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('asset_maintenance')) {
            Schema::table('asset_maintenance', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
