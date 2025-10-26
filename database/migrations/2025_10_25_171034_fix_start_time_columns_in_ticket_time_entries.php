<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ticket_time_entries', function (Blueprint $table) {
            // First, copy data from started_at to start_time where start_time is null
            DB::statement('UPDATE ticket_time_entries SET start_time = started_at WHERE start_time IS NULL AND started_at IS NOT NULL');
            
            // Make start_time nullable since we're using started_at in the new code
            $table->dateTime('start_time')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_time_entries', function (Blueprint $table) {
            // Make start_time not nullable again
            $table->dateTime('start_time')->nullable(false)->change();
        });
    }
};
