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
        Schema::create('ticket_calendar_events', function (Blueprint $table) {
            $table->id();
                        $table->foreignId('company_id')->constrained()->onDelete('cascade');
                        $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
                        $table->string('title');
                        $table->text('description')->nullable();
                        $table->dateTime('start_time');
                        $table->dateTime('end_time');
                        $table->boolean('all_day')->default(false);
                        $table->json('attendees')->nullable();
                        $table->string('location')->nullable();
                        $table->timestamps();
                        $table->json('attendee_emails')->nullable();
                        $table->boolean('is_onsite')->default(false);
                        $table->boolean('is_all_day')->default(false);
                        $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled', 'rescheduled'])->default('scheduled');
                        $table->text('notes')->nullable();
                        $table->json('reminders')->nullable();

                        $table->index(['company_id', 'ticket_id']);
                        $table->index(['company_id', 'start_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_calendar_events');
    }
};
