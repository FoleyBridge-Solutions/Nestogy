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
        Schema::table('ticket_time_entries', function (Blueprint $table) {
            // Replace legacy timestamp fields with modern ones
            $table->dropColumn(['start_time', 'end_time']);
            $table->dateTime('started_at')->nullable()->after('work_date');
            $table->dateTime('ended_at')->nullable()->after('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_time_entries', function (Blueprint $table) {
            // Restore legacy timestamp fields
            $table->dropColumn(['started_at', 'ended_at']);
            $table->dateTime('start_time')->nullable()->after('work_date');
            $table->dateTime('end_time')->nullable()->after('start_time');
        });
    }
};
