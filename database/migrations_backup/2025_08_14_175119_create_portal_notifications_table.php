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
        Schema::create('portal_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('client_id')->index();

            // Basic notification properties
            $table->string('type')->index();
            $table->string('category')->nullable()->index();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent', 'critical'])->default('normal')->index();
            $table->string('title');
            $table->text('message');
            $table->text('description')->nullable();
            $table->json('data')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->string('action_url')->nullable();
            $table->string('action_text')->nullable();

            // Display and delivery preferences
            $table->boolean('show_in_portal')->default(true)->index();
            $table->boolean('send_email')->default(false);
            $table->boolean('send_sms')->default(false);
            $table->boolean('send_push')->default(false);
            $table->json('delivery_channels')->nullable();

            // Email delivery
            $table->string('email_subject')->nullable();
            $table->text('email_body')->nullable();
            $table->string('email_template')->nullable();
            $table->timestamp('email_sent_at')->nullable();
            $table->boolean('email_delivered')->nullable();
            $table->string('email_error')->nullable();

            // SMS delivery
            $table->string('sms_message')->nullable();
            $table->timestamp('sms_sent_at')->nullable();
            $table->boolean('sms_delivered')->nullable();
            $table->string('sms_error')->nullable();

            // Push notification delivery
            $table->string('push_title')->nullable();
            $table->text('push_body')->nullable();
            $table->json('push_data')->nullable();
            $table->timestamp('push_sent_at')->nullable();
            $table->boolean('push_delivered')->nullable();
            $table->string('push_error')->nullable();

            // Status and interaction tracking
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed', 'cancelled'])->default('pending')->index();
            $table->boolean('is_read')->default(false)->index();
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_dismissed')->default(false)->index();
            $table->timestamp('dismissed_at')->nullable();
            $table->boolean('requires_action')->default(false);
            $table->boolean('action_completed')->default(false);
            $table->timestamp('action_completed_at')->nullable();

            // Scheduling and expiration
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->boolean('is_recurring')->default(false);
            $table->string('recurring_pattern')->nullable();
            $table->timestamp('next_occurrence')->nullable();

            // Advanced features
            $table->json('target_conditions')->nullable();
            $table->json('personalization_data')->nullable();
            $table->string('language', 10)->default('en');
            $table->string('timezone')->nullable();

            // Related models
            $table->unsignedBigInteger('invoice_id')->nullable()->index();
            $table->unsignedBigInteger('payment_id')->nullable()->index();
            $table->unsignedBigInteger('ticket_id')->nullable()->index();
            $table->unsignedBigInteger('contract_id')->nullable()->index();
            $table->string('related_model_type')->nullable();
            $table->unsignedBigInteger('related_model_id')->nullable();

            // Threading and grouping
            $table->string('group_key')->nullable()->index();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->integer('thread_position')->nullable();
            $table->boolean('is_summary')->default(false);

            // Analytics and tracking
            $table->json('tracking_data')->nullable();
            $table->integer('view_count')->default(0);
            $table->timestamp('first_viewed_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->integer('click_count')->default(0);
            $table->timestamp('first_clicked_at')->nullable();
            $table->timestamp('last_clicked_at')->nullable();

            // A/B testing and experimentation
            $table->string('variant')->nullable();
            $table->string('campaign_id')->nullable();
            $table->json('experiment_data')->nullable();

            // Acknowledgment
            $table->boolean('requires_acknowledgment')->default(false);
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('acknowledgment_method')->nullable();

            // Audit trail
            $table->json('audit_trail')->nullable();

            // User preferences and controls
            $table->boolean('respects_do_not_disturb')->default(true);
            $table->json('client_preferences')->nullable();
            $table->boolean('can_be_disabled')->default(true);
            $table->string('frequency_limit')->nullable();

            // System integration
            $table->string('source_system')->nullable();
            $table->string('external_id')->nullable();
            $table->json('webhook_data')->nullable();
            $table->boolean('trigger_webhooks')->default(false);

            // Extensibility
            $table->json('metadata')->nullable();
            $table->json('custom_fields')->nullable();
            $table->text('internal_notes')->nullable();

            // User tracking
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['company_id', 'client_id'], 'portal_notifications_company_client_idx');
            $table->index(['show_in_portal', 'is_dismissed', 'expires_at'], 'portal_notifications_display_idx');
            $table->index(['scheduled_at', 'status'], 'portal_notifications_schedule_idx');
            $table->index(['type', 'category'], 'portal_notifications_type_idx');

            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('portal_notifications')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portal_notifications');
    }
};
