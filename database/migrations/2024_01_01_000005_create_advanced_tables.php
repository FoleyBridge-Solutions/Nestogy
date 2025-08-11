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