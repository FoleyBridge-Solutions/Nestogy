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
            if (!Schema::hasColumn('ticket_time_entries', 'metadata')) {
                $table->json('metadata')->nullable()->after('amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_time_entries', function (Blueprint $table) {
            if (Schema::hasColumn('ticket_time_entries', 'metadata')) {
                $table->dropColumn('metadata');
            }
        });
    }
};
