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
        Schema::create('mail_queue', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->comment('User who initiated the email');
            
            // Email details
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->string('to_email');
            $table->string('to_name')->nullable();
            $table->json('cc')->nullable();
            $table->json('bcc')->nullable();
            $table->string('reply_to')->nullable();
            $table->string('subject');
            $table->longText('html_body')->nullable();
            $table->longText('text_body')->nullable();
            $table->json('attachments')->nullable();
            $table->json('headers')->nullable();
            
            // Template information
            $table->string('template')->nullable()->comment('Template name if using template');
            $table->json('template_data')->nullable()->comment('Data passed to template');
            
            // Queue management
            $table->enum('status', ['pending', 'processing', 'sent', 'failed', 'bounced', 'complained', 'cancelled'])->default('pending');
            $table->enum('priority', ['low', 'normal', 'high', 'critical'])->default('normal');
            $table->integer('attempts')->default(0);
            $table->integer('max_attempts')->default(3);
            $table->timestamp('scheduled_at')->nullable()->comment('When to send the email');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            
            // Error tracking
            $table->text('last_error')->nullable();
            $table->json('error_log')->nullable()->comment('History of all errors');
            $table->string('failure_reason')->nullable()->comment('Categorized failure reason');
            
            // Provider information
            $table->string('mailer')->default('smtp')->comment('Mail driver used (smtp, ses, mailgun, etc)');
            $table->string('message_id')->nullable()->comment('Provider message ID');
            $table->json('provider_response')->nullable();
            
            // Tracking
            $table->string('tracking_token')->nullable()->unique();
            $table->timestamp('opened_at')->nullable();
            $table->integer('open_count')->default(0);
            $table->json('opens')->nullable()->comment('History of all opens');
            $table->integer('click_count')->default(0);
            $table->json('clicks')->nullable()->comment('History of all clicks');
            
            // Categorization
            $table->string('category')->nullable()->comment('Email category (invoice, notification, marketing, etc)');
            $table->string('related_type')->nullable()->comment('Related model type');
            $table->unsignedBigInteger('related_id')->nullable()->comment('Related model ID');
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index('uuid');
            $table->index(['company_id', 'status', 'scheduled_at']);
            $table->index(['status', 'scheduled_at']);
            $table->index(['status', 'attempts', 'next_retry_at']);
            $table->index('to_email');
            $table->index('message_id');
            $table->index('tracking_token');
            $table->index(['related_type', 'related_id']);
            $table->index('category');
            $table->index('created_at');
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
        
        // Create table for email templates
        Schema::create('mail_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->string('category')->comment('invoice, notification, marketing, system, etc');
            $table->string('subject');
            $table->longText('html_template');
            $table->longText('text_template')->nullable();
            $table->json('available_variables')->nullable()->comment('List of variables that can be used in template');
            $table->json('default_data')->nullable()->comment('Default values for variables');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false)->comment('System templates cannot be deleted');
            $table->json('settings')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'category', 'is_active']);
            $table->index('name');
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mail_templates');
        Schema::dropIfExists('mail_queue');
    }
};
