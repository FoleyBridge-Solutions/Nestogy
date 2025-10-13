<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration adds the next_maintenance_date column to the assets table.
     * This column is used to track when the next scheduled maintenance is due for an asset.
     * It's nullable because not all assets may have scheduled maintenance.
     */
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            // Add next_maintenance_date column after the warranty_expire column
            // for logical grouping with other maintenance-related date fields
            $table->date('next_maintenance_date')
                ->nullable()
                ->after('warranty_expire')
                ->comment('Date when the next scheduled maintenance is due for this asset');

            // Add index for performance when querying assets by maintenance date
            // This is important for dashboard views and maintenance reports
            $table->index('next_maintenance_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * WARNING: This will permanently remove the next_maintenance_date column
     * and any data stored in it. Ensure data is backed up if needed before rollback.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            // Drop the index first before dropping the column
            $table->dropIndex(['next_maintenance_date']);

            // Drop the column
            $table->dropColumn('next_maintenance_date');
        });
    }
};
