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
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id');
            $table->string('type'); // invoice_due, payment_received, service_outage, maintenance, account_update
            $table->string('category')->nullable(); // billing, technical, marketing, security, system
            $table->string('priority')->default('normal'); // low, normal, high, urgent, critical
            $table->string('title');
            $table->text('message');
            $table->text('description')->nullable(); // Extended description
            $table->json('data')->nullable(); // Structured notification data
            $table->string('icon')->nullable(); // Icon class or URL
            $table->string('color')->nullable(); // Notification color theme
            $table->string('action_url')->nullable(); // URL for action button
            $table->string('action_text')->nullable(); // Action button text
            
            // Delivery Channels
            $table->boolean('show_in_portal')->default(true);
            $table->boolean('send_email')->default(false);
            $table->boolean('send_sms')->default(false);
            $table->boolean('send_push')->default(false);
            $table->json('delivery_channels')->nullable(); // Additional delivery methods
            
            // Email Delivery
            $table->string('email_subject')->nullable();
            $table->text('email_body')->nullable();
            $table->string('email_template')->nullable();
            $table->timestamp('email_sent_at')->nullable();
            $table->boolean('email_delivered')->nullable();
            $table->text('email_error')->nullable();
            
            // SMS Delivery
            $table->text('sms_message')->nullable();
            $table->timestamp('sms_sent_at')->nullable();
            $table->boolean('sms_delivered')->nullable();
            $table->text('sms_error')->nullable();
            
            // Push Notification
            $table->string('push_title')->nullable();
            $table->text('push_body')->nullable();
            $table->json('push_data')->nullable();
            $table->timestamp('push_sent_at')->nullable();
            $table->boolean('push_delivered')->nullable();
            $table->text('push_error')->nullable();
            
            // Status and Tracking
            $table->string('status')->default('pending'); // pending, sent, delivered, read, failed, cancelled
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_dismissed')->default(false);
            $table->timestamp('dismissed_at')->nullable();
            $table->boolean('requires_action')->default(false);
            $table->boolean('action_completed')->default(false);
            $table->timestamp('action_completed_at')->nullable();
            
            // Scheduling
            $table->timestamp('scheduled_at')->nullable(); // When to send
            $table->timestamp('expires_at')->nullable(); // When notification expires
            $table->boolean('is_recurring')->default(false);
            $table->string('recurring_pattern')->nullable(); // For recurring notifications
            $table->timestamp('next_occurrence')->nullable();
            
            // Targeting and Personalization
            $table->json('target_conditions')->nullable(); // Conditions for showing notification
            $table->json('personalization_data')->nullable(); // Data for personalizing message
            $table->string('language', 5)->default('en'); // Notification language
            $table->string('timezone')->nullable(); // Client timezone for scheduling
            
            // Related Objects
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->unsignedBigInteger('ticket_id')->nullable();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->string('related_model_type')->nullable(); // Polymorphic relation
            $table->unsignedBigInteger('related_model_id')->nullable();
            
            // Grouping and Threading
            $table->string('group_key')->nullable(); // Group related notifications
            $table->unsignedBigInteger('parent_id')->nullable(); // Thread notifications
            $table->integer('thread_position')->nullable(); // Position in thread
            $table->boolean('is_summary')->default(false); // Summary notification
            
            // Analytics and Tracking
            $table->json('tracking_data')->nullable(); // Analytics tracking
            $table->integer('view_count')->default(0);
            $table->timestamp('first_viewed_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->integer('click_count')->default(0);
            $table->timestamp('first_clicked_at')->nullable();
            $table->timestamp('last_clicked_at')->nullable();
            
            // A/B Testing and Optimization
            $table->string('variant')->nullable(); // A/B test variant
            $table->string('campaign_id')->nullable(); // Marketing campaign ID
            $table->json('experiment_data')->nullable(); // A/B testing data
            
            // Compliance and Audit
            $table->boolean('requires_acknowledgment')->default(false);
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('acknowledgment_method')->nullable(); // How it was acknowledged
            $table->json('audit_trail')->nullable(); // Audit information
            
            // Client Preferences
            $table->boolean('respects_do_not_disturb')->default(true);
            $table->json('client_preferences')->nullable(); // Client notification preferences
            $table->boolean('can_be_disabled')->default(true);
            $table->string('frequency_limit')->nullable(); // Rate limiting
            
            // System Integration
            $table->string('source_system')->nullable(); // Which system generated this
            $table->string('external_id')->nullable(); // ID in external system
            $table->json('webhook_data')->nullable(); // Data for webhook callbacks
            $table->boolean('trigger_webhooks')->default(false);
            
            // Metadata
            $table->json('metadata')->nullable(); // Additional metadata
            $table->json('custom_fields')->nullable(); // Custom fields
            $table->text('internal_notes')->nullable(); // Internal notes
            
            // Lifecycle
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('company_id');
            $table->index('client_id');
            $table->index('type');
            $table->index('category');
            $table->index('priority');
            $table->index('status');
            $table->index('is_read');
            $table->index('is_dismissed');
            $table->index('scheduled_at');
            $table->index('expires_at');
            $table->index('created_at');
            $table->index('read_at');
            $table->index('invoice_id');
            $table->index('payment_id');
            $table->index('ticket_id');
            $table->index('contract_id');
            $table->index('group_key');
            $table->index('parent_id');
            $table->index('show_in_portal');
            $table->index(['company_id', 'client_id']);
            $table->index(['client_id', 'status']);
            $table->index(['client_id', 'is_read']);
            $table->index(['client_id', 'type']);
            $table->index(['client_id', 'category']);
            $table->index(['status', 'scheduled_at']);
            $table->index(['is_recurring', 'next_occurrence']);
            $table->index(['related_model_type', 'related_model_id']);
            $table->index('created_by');
            $table->index('updated_by');

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('cascade');
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('portal_notifications')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
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