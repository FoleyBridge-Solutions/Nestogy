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
        // Extended client management tables
        Schema::create('client_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_billing')->default(false);
            $table->boolean('is_technical')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'client_id']);
            $table->index(['company_id', 'email']);
            $table->index(['company_id', 'is_primary']);
        });

        Schema::create('client_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['billing', 'shipping', 'service', 'other'])->default('billing');
            $table->string('address');
            $table->string('address2')->nullable();
            $table->string('city');
            $table->string('state');
            $table->string('zip');
            $table->string('country', 2)->default('US');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            
            $table->index(['company_id', 'client_id']);
            $table->index(['company_id', 'type']);
        });

        Schema::create('client_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->text('description')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('is_confidential')->default(false);
            $table->timestamps();
            
            $table->index(['company_id', 'client_id']);
            $table->index(['company_id', 'type']);
        });

        Schema::create('client_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->bigInteger('file_size');
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'client_id']);
            $table->index(['company_id', 'category']);
        });

        Schema::create('client_licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('software_name');
            $table->string('license_key');
            $table->string('version')->nullable();
            $table->integer('seats')->default(1);
            $table->date('purchase_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'client_id']);
            $table->index(['company_id', 'expiry_date']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('client_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('service_name');
            $table->string('username');
            $table->text('password');
            $table->string('url')->nullable();
            $table->text('notes')->nullable();
            $table->json('additional_fields')->nullable();
            $table->boolean('is_shared')->default(false);
            $table->timestamps();
            
            $table->index(['company_id', 'client_id']);
            $table->index(['company_id', 'service_name']);
        });

        Schema::create('client_networks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('network_address');
            $table->string('subnet_mask');
            $table->string('gateway')->nullable();
            $table->string('dns_primary')->nullable();
            $table->string('dns_secondary')->nullable();
            $table->integer('vlan_id')->nullable();
            $table->text('description')->nullable();
            $table->enum('type', ['lan', 'wan', 'dmz', 'guest', 'management'])->default('lan');
            $table->timestamps();
            
            $table->index(['company_id', 'client_id']);
            $table->index(['company_id', 'type']);
        });

        Schema::create('client_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['managed', 'monitoring', 'backup', 'security', 'support', 'other'])->default('other');
            $table->decimal('monthly_rate', 10, 2)->default(0);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->json('configuration')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'client_id']);
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('client_vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('account_number')->nullable();
            $table->text('notes')->nullable();
            $table->enum('relationship', ['vendor', 'supplier', 'partner', 'contractor'])->default('vendor');
            $table->timestamps();
            
            $table->index(['company_id', 'client_id']);
            $table->index(['company_id', 'relationship']);
        });

        // Client IT Documentation
        Schema::create('client_it_documentation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('authored_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('it_category')->nullable();
            $table->json('system_references')->nullable();
            $table->json('ip_addresses')->nullable();
            $table->json('software_versions')->nullable();
            $table->json('compliance_requirements')->nullable();
            $table->string('review_schedule')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamp('next_review_at')->nullable();
            $table->string('access_level')->nullable();
            $table->json('procedure_steps')->nullable();
            $table->json('network_diagram')->nullable();
            $table->json('related_entities')->nullable();
            $table->json('tags')->nullable();
            $table->integer('version')->default(1);
            $table->foreignId('parent_document_id')->nullable()->constrained('client_it_documentation')->onDelete('cascade');
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('filename')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('file_hash')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->integer('access_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('enabled_tabs')->nullable();
            $table->json('tab_configuration')->nullable();
            $table->string('status')->default('active');
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('template_used')->nullable();
            $table->json('ports')->nullable();
            $table->json('api_endpoints')->nullable();
            $table->json('ssl_certificates')->nullable();
            $table->json('dns_entries')->nullable();
            $table->json('firewall_rules')->nullable();
            $table->json('vpn_settings')->nullable();
            $table->json('hardware_references')->nullable();
            $table->json('environment_variables')->nullable();
            $table->json('procedure_diagram')->nullable();
            $table->json('rollback_procedures')->nullable();
            $table->json('prerequisites')->nullable();
            $table->string('data_classification')->nullable();
            $table->boolean('encryption_required')->default(false);
            $table->json('audit_requirements')->nullable();
            $table->json('security_controls')->nullable();
            $table->json('external_resources')->nullable();
            $table->json('vendor_contacts')->nullable();
            $table->json('support_contracts')->nullable();
            $table->json('test_cases')->nullable();
            $table->json('validation_checklist')->nullable();
            $table->json('performance_benchmarks')->nullable();
            $table->json('health_checks')->nullable();
            $table->json('automation_scripts')->nullable();
            $table->json('integrations')->nullable();
            $table->json('webhooks')->nullable();
            $table->json('scheduled_tasks')->nullable();
            $table->decimal('uptime_requirement', 5, 2)->nullable();
            $table->integer('rto')->nullable(); // Recovery Time Objective in minutes
            $table->integer('rpo')->nullable(); // Recovery Point Objective in minutes
            $table->json('performance_metrics')->nullable();
            $table->json('alert_thresholds')->nullable();
            $table->json('escalation_paths')->nullable();
            $table->text('change_summary')->nullable();
            $table->json('change_log')->nullable();
            $table->boolean('requires_technical_review')->default(false);
            $table->boolean('requires_management_approval')->default(false);
            $table->json('approval_history')->nullable();
            $table->json('review_comments')->nullable();
            $table->json('custom_fields')->nullable();
            $table->integer('documentation_completeness')->default(0);
            $table->boolean('is_template')->default(false);
            $table->string('template_category')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['company_id', 'client_id']);
            $table->index(['company_id', 'it_category']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'is_template']);
            $table->index('authored_by');
            $table->index('parent_document_id');
            $table->index('last_reviewed_at');
            $table->index('next_review_at');
        });

        // Advanced ticket system tables
        Schema::create('ticket_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->json('default_fields')->nullable();
            $table->text('instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'category']);
        });

        Schema::create('recurring_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('template_id')->nullable()->constrained('ticket_templates')->onDelete('set null');
            $table->string('title');
            $table->text('description');
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'annually'])->default('monthly');
            $table->integer('interval_value')->default(1);
            $table->dateTime('next_run');
            $table->dateTime('last_run')->nullable();
            $table->enum('status', ['active', 'paused', 'completed'])->default('active');
            $table->json('configuration')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'client_id']);
            $table->index(['company_id', 'next_run']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('ticket_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('conditions');
            $table->json('actions');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'sort_order']);
        });

        Schema::create('ticket_status_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('from_status');
            $table->string('to_status');
            $table->string('transition_name');
            $table->boolean('requires_approval')->default(false);
            $table->json('allowed_roles')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'from_status']);
            $table->unique(['company_id', 'from_status', 'to_status'], 'ticket_status_unique');
        });

        Schema::create('ticket_time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('description');
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->integer('minutes')->nullable();
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->boolean('is_billable')->default(true);
            $table->boolean('is_billed')->default(false);
            $table->timestamps();
            
            $table->index(['company_id', 'ticket_id']);
            $table->index(['company_id', 'user_id']);
            $table->index(['company_id', 'is_billable']);
        });

        Schema::create('ticket_priority_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
            $table->integer('priority_score');
            $table->dateTime('queue_time');
            $table->json('scoring_factors');
            $table->boolean('is_escalated')->default(false);
            $table->timestamps();
            
            $table->index(['company_id', 'priority_score']);
            $table->index(['company_id', 'queue_time']);
            $table->unique(['company_id', 'ticket_id']);
        });

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
        Schema::dropIfExists('ticket_priority_queue');
        Schema::dropIfExists('ticket_time_entries');
        Schema::dropIfExists('ticket_status_transitions');
        Schema::dropIfExists('ticket_workflows');
        Schema::dropIfExists('recurring_tickets');
        Schema::dropIfExists('ticket_templates');
        Schema::dropIfExists('client_vendors');
        Schema::dropIfExists('client_services');
        Schema::dropIfExists('client_networks');
        Schema::dropIfExists('client_credentials');
        Schema::dropIfExists('client_licenses');
        Schema::dropIfExists('client_files');
        Schema::dropIfExists('client_documents');
        Schema::dropIfExists('client_addresses');
        Schema::dropIfExists('client_contacts');
    }
};