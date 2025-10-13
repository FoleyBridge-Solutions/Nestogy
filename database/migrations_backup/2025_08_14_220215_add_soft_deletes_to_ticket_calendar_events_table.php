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
        Schema::table('ticket_calendar_events', function (Blueprint $table) {
            $table->softDeletes();
            $table->json('attendee_emails')->nullable()->after('location');
            $table->boolean('is_onsite')->default(false)->after('attendee_emails');
            $table->boolean('is_all_day')->default(false)->after('is_onsite');
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled', 'rescheduled'])->default('scheduled')->after('is_all_day');
            $table->text('notes')->nullable()->after('status');
            $table->json('reminders')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_calendar_events', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['attendee_emails', 'is_onsite', 'is_all_day', 'status', 'notes', 'reminders']);
        });
    }
};
