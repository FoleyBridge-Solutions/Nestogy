<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Lead Sources table
        Schema::create('lead_sources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->string('type')->default('manual'); // manual, website, referral, campaign, import
            $table->text('description')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index(['company_id', 'is_active']);
        });

        // Leads table
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('lead_source_id')->nullable();
            $table->unsignedBigInteger('assigned_user_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable(); // If converted to client

            // Contact Information
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('company_name')->nullable();
            $table->string('title')->nullable();
            $table->string('website')->nullable();

            // Address Information
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('country')->nullable();

            // Lead Details
            $table->enum('status', ['new', 'contacted', 'qualified', 'unqualified', 'nurturing', 'converted', 'lost'])->default('new');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->string('industry')->nullable();
            $table->integer('company_size')->nullable(); // Number of employees
            $table->decimal('estimated_value', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->json('custom_fields')->nullable();

            // Scoring
            $table->integer('total_score')->default(0);
            $table->integer('demographic_score')->default(0);
            $table->integer('behavioral_score')->default(0);
            $table->integer('fit_score')->default(0);
            $table->integer('urgency_score')->default(0);
            $table->timestamp('last_scored_at')->nullable();

            // Tracking
            $table->timestamp('first_contact_date')->nullable();
            $table->timestamp('last_contact_date')->nullable();
            $table->timestamp('qualified_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('utm_term')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('lead_source_id')->references('id')->on('lead_sources')->onDelete('set null');
            $table->foreign('assigned_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'total_score']);
            $table->index(['assigned_user_id', 'status']);
            $table->index(['email']);
        });

        // Lead Activities table
        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('type'); // email_sent, email_opened, email_clicked, call_made, meeting_scheduled, etc.
            $table->string('subject')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Store additional data like email ID, campaign ID, etc.
            $table->timestamp('activity_date');
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            $table->index(['lead_id', 'activity_date']);
            $table->index(['type', 'activity_date']);
        });

        // Marketing Campaigns table
        Schema::create('marketing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by_user_id');

            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['email', 'nurture', 'drip', 'event', 'webinar', 'content'])->default('email');
            $table->enum('status', ['draft', 'scheduled', 'active', 'paused', 'completed', 'archived'])->default('draft');

            // Campaign Settings
            $table->json('settings')->nullable(); // Store campaign-specific settings
            $table->json('target_criteria')->nullable(); // Lead qualification criteria
            $table->boolean('auto_enroll')->default(false);

            // Scheduling
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();

            // Metrics
            $table->integer('total_recipients')->default(0);
            $table->integer('total_sent')->default(0);
            $table->integer('total_delivered')->default(0);
            $table->integer('total_opened')->default(0);
            $table->integer('total_clicked')->default(0);
            $table->integer('total_replied')->default(0);
            $table->integer('total_unsubscribed')->default(0);
            $table->integer('total_converted')->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('cascade');

            $table->index(['company_id', 'status']);
            $table->index(['status', 'start_date']);
        });

        // Campaign Sequences table (for multi-step campaigns)
        Schema::create('campaign_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');

            $table->string('name');
            $table->integer('step_number');
            $table->integer('delay_days')->default(0); // Days after previous step
            $table->integer('delay_hours')->default(0); // Hours after previous step

            // Email details
            $table->string('subject_line');
            $table->text('email_template'); // HTML content
            $table->text('email_text')->nullable(); // Plain text version

            // Conditions
            $table->json('send_conditions')->nullable(); // Conditions to send this step
            $table->json('skip_conditions')->nullable(); // Conditions to skip this step

            // Settings
            $table->boolean('is_active')->default(true);
            $table->time('send_time')->default('09:00:00'); // Preferred send time
            $table->json('send_days')->nullable(); // Days of week to send [1,2,3,4,5]

            $table->timestamps();

            $table->foreign('campaign_id')->references('id')->on('marketing_campaigns')->onDelete('cascade');

            $table->index(['campaign_id', 'step_number']);
        });

        // Campaign Enrollments table (tracks who is in which campaigns)
        Schema::create('campaign_enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();

            $table->enum('status', ['enrolled', 'active', 'completed', 'paused', 'unsubscribed', 'bounced'])->default('enrolled');
            $table->integer('current_step')->default(0);
            $table->timestamp('enrolled_at');
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('next_send_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Tracking
            $table->integer('emails_sent')->default(0);
            $table->integer('emails_opened')->default(0);
            $table->integer('emails_clicked')->default(0);
            $table->boolean('converted')->default(false);
            $table->timestamp('converted_at')->nullable();

            $table->timestamps();

            $table->foreign('campaign_id')->references('id')->on('marketing_campaigns')->onDelete('cascade');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');

            $table->index(['campaign_id', 'status']);
            $table->index(['next_send_at']);
            $table->index(['lead_id', 'status']);
            $table->index(['contact_id', 'status']);
        });

        // Email Tracking table
        Schema::create('email_tracking', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('tracking_id')->unique(); // UUID for tracking

            // Recipients
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->string('recipient_email');

            // Campaign/Email details
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('campaign_sequence_id')->nullable();
            $table->string('email_type')->default('campaign'); // campaign, transactional, manual
            $table->string('subject_line');

            // Delivery tracking
            $table->enum('status', ['sent', 'delivered', 'bounced', 'failed'])->default('sent');
            $table->timestamp('sent_at');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->text('bounce_reason')->nullable();

            // Engagement tracking
            $table->timestamp('first_opened_at')->nullable();
            $table->timestamp('last_opened_at')->nullable();
            $table->integer('open_count')->default(0);
            $table->timestamp('first_clicked_at')->nullable();
            $table->timestamp('last_clicked_at')->nullable();
            $table->integer('click_count')->default(0);
            $table->timestamp('replied_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();

            // User agent tracking
            $table->text('user_agent')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('location')->nullable();

            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('campaign_id')->references('id')->on('marketing_campaigns')->onDelete('cascade');
            $table->foreign('campaign_sequence_id')->references('id')->on('campaign_sequences')->onDelete('cascade');

            $table->index(['tracking_id']);
            $table->index(['recipient_email']);
            $table->index(['campaign_id', 'sent_at']);
            $table->index(['lead_id', 'sent_at']);
            $table->index(['contact_id', 'sent_at']);
        });

        // Attribution Touchpoints table
        Schema::create('attribution_touchpoints', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();

            $table->string('touchpoint_type'); // email_open, email_click, website_visit, form_submit, etc.
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->string('source')->nullable(); // utm_source
            $table->string('medium')->nullable(); // utm_medium
            $table->string('campaign')->nullable(); // utm_campaign
            $table->string('content')->nullable(); // utm_content
            $table->string('term')->nullable(); // utm_term

            $table->string('page_url')->nullable();
            $table->string('referrer_url')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            $table->json('metadata')->nullable(); // Additional tracking data
            $table->timestamp('touched_at');

            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('campaign_id')->references('id')->on('marketing_campaigns')->onDelete('cascade');

            $table->index(['lead_id', 'touched_at']);
            $table->index(['contact_id', 'touched_at']);
            $table->index(['client_id', 'touched_at']);
            $table->index(['campaign_id', 'touched_at']);
            $table->index(['touchpoint_type', 'touched_at']);
        });

        // Conversion Events table
        Schema::create('conversion_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();

            $table->string('event_type'); // lead_qualified, opportunity_created, deal_closed, invoice_paid
            $table->decimal('value', 12, 2)->default(0); // Revenue value
            $table->string('currency', 3)->default('USD');

            // Attribution
            $table->unsignedBigInteger('attributed_campaign_id')->nullable();
            $table->string('attribution_model')->default('last_touch'); // first_touch, last_touch, multi_touch
            $table->json('attribution_data')->nullable(); // Detailed attribution breakdown

            $table->json('metadata')->nullable();
            $table->timestamp('converted_at');

            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('attributed_campaign_id')->references('id')->on('marketing_campaigns')->onDelete('set null');

            $table->index(['event_type', 'converted_at']);
            $table->index(['attributed_campaign_id', 'converted_at']);
            $table->index(['company_id', 'converted_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('conversion_events');
        Schema::dropIfExists('attribution_touchpoints');
        Schema::dropIfExists('email_tracking');
        Schema::dropIfExists('campaign_enrollments');
        Schema::dropIfExists('campaign_sequences');
        Schema::dropIfExists('marketing_campaigns');
        Schema::dropIfExists('lead_activities');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('lead_sources');
    }
};
