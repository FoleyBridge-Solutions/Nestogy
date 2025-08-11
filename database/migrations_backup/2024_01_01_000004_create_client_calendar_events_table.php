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
        Schema::create('client_calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('event_type', ['meeting', 'appointment', 'consultation', 'maintenance', 'deadline', 'reminder', 'follow_up', 'presentation', 'training', 'review'])->default('meeting');
            $table->datetime('start_time');
            $table->datetime('end_time');
            $table->boolean('all_day')->default(false);
            $table->string('timezone')->default('UTC');
            $table->string('location')->nullable();
            $table->text('location_details')->nullable();
            $table->boolean('virtual_meeting')->default(false);
            $table->string('meeting_url')->nullable();
            $table->text('meeting_details')->nullable();
            $table->enum('status', ['scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled', 'rescheduled', 'no_show'])->default('scheduled');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->json('attendees')->nullable(); // Array of attendee objects
            $table->integer('max_attendees')->nullable();
            $table->boolean('requires_confirmation')->default(false);
            $table->json('confirmation_status')->nullable(); // Attendee confirmation tracking
            $table->boolean('recurring')->default(false);
            $table->enum('recurrence_type', ['daily', 'weekly', 'monthly', 'yearly'])->nullable();
            $table->integer('recurrence_interval')->default(1);
            $table->json('recurrence_days')->nullable(); // For weekly: [1,3,5] for Mon,Wed,Fri
            $table->date('recurrence_end_date')->nullable();
            $table->integer('recurrence_count')->nullable();
            $table->string('parent_event_id')->nullable(); // For recurring events
            $table->boolean('reminder_enabled')->default(true);
            $table->json('reminder_times')->nullable(); // [15, 60, 1440] minutes before
            $table->json('reminder_methods')->nullable(); // ['email', 'sms', 'push']
            $table->timestamp('last_reminder_sent')->nullable();
            $table->json('notification_history')->nullable();
            $table->json('agenda_items')->nullable(); // Meeting agenda
            $table->text('preparation_notes')->nullable();
            $table->text('meeting_notes')->nullable();
            $table->json('action_items')->nullable(); // Follow-up tasks
            $table->text('outcome_summary')->nullable();
            $table->enum('outcome_status', ['successful', 'needs_followup', 'rescheduled', 'cancelled'])->nullable();
            $table->json('attachments')->nullable(); // File attachments
            $table->json('related_documents')->nullable(); // Links to client documents
            $table->decimal('estimated_duration_hours', 5, 2)->nullable();
            $table->decimal('actual_duration_hours', 5, 2)->nullable();
            $table->decimal('preparation_time_hours', 5, 2)->nullable();
            $table->decimal('cost_estimate', 10, 2)->default(0);
            $table->decimal('actual_cost', 10, 2)->default(0);
            $table->string('assigned_to')->nullable(); // Staff member
            $table->json('team_members')->nullable(); // Multiple staff
            $table->json('client_contacts')->nullable(); // Client attendees
            $table->boolean('billable')->default(true);
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->text('billing_notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->json('integration_data')->nullable(); // External calendar sync
            $table->string('external_event_id')->nullable();
            $table->timestamp('last_synced')->nullable();
            $table->json('alerts')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('accessed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'client_id']);
            $table->index(['start_time', 'end_time']);
            $table->index(['status', 'company_id']);
            $table->index('event_type');
            $table->index('priority');
            $table->index('recurring');
            $table->index('assigned_to');
            $table->index('all_day');
            $table->index(['start_time', 'status']);
            $table->index('external_event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_calendar_events');
    }
};