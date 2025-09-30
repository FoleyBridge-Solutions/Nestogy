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
        // Check which columns exist before dropping
        $existingColumns = collect(Schema::getColumnListing('ticket_time_entries'));

        Schema::table('ticket_time_entries', function (Blueprint $table) use ($existingColumns) {
            // Drop the old timestamp fields if they exist
            $columnsToDrop = [];
            if ($existingColumns->contains('start_time')) {
                $columnsToDrop[] = 'start_time';
            }
            if ($existingColumns->contains('end_time')) {
                $columnsToDrop[] = 'end_time';
            }

            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }

            // Add modern nullable timestamp fields if they don't exist
            if (! $existingColumns->contains('started_at')) {
                $table->dateTime('started_at')->nullable()->after('work_date');
            }
            if (! $existingColumns->contains('ended_at')) {
                $table->dateTime('ended_at')->nullable()->after('started_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_time_entries', function (Blueprint $table) {
            // Restore old timestamp fields (should not be needed in prerelease)
            $table->dropColumn(['started_at', 'ended_at']);
            $table->dateTime('start_time')->notNullable()->after('work_date');
            $table->dateTime('end_time')->nullable()->after('start_time');
        });
    }
};
