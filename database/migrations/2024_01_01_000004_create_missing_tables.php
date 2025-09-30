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
        // Laravel system tables
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        // Client management tables
        Schema::create('client_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('domain');
            $table->string('issuer')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date');
            $table->enum('type', ['ssl', 'wildcard', 'ev', 'dv', 'ov'])->default('ssl');
            $table->enum('status', ['active', 'expired', 'pending', 'revoked'])->default('active');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'client_id']);
            $table->index(['company_id', 'expiry_date']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('client_racks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('location')->nullable();
            $table->integer('units')->default(42);
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'client_id']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('client_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('domain');
            $table->string('registrar')->nullable();
            $table->date('registration_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->enum('status', ['active', 'expired', 'pending', 'suspended'])->default('active');
            $table->text('notes')->nullable();
            $table->json('dns_records')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'client_id']);
            $table->index(['company_id', 'expiry_date']);
            $table->index(['company_id', 'domain']);
        });

        Schema::create('client_calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->boolean('all_day')->default(false);
            $table->enum('type', ['maintenance', 'meeting', 'project', 'other'])->default('other');
            $table->json('attendees')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'client_id']);
            $table->index(['company_id', 'start_time']);
            $table->index(['company_id', 'type']);
        });

        Schema::create('client_recurring_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('invoice_number');
            $table->decimal('amount', 10, 2);
            $table->enum('frequency', ['monthly', 'quarterly', 'semi-annually', 'annually'])->default('monthly');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_invoice_date');
            $table->enum('status', ['active', 'paused', 'cancelled'])->default('active');
            $table->json('line_items');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'client_id']);
            $table->index(['company_id', 'next_invoice_date']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('client_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('quote_number');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->date('quote_date');
            $table->date('expiry_date')->nullable();
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'expired'])->default('draft');
            $table->json('line_items');
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'client_id']);
            $table->index(['company_id', 'quote_date']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('client_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('purpose');
            $table->text('description')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->decimal('mileage', 8, 2)->nullable();
            $table->decimal('expense_amount', 10, 2)->default(0);
            $table->enum('status', ['planned', 'in_progress', 'completed', 'cancelled'])->default('planned');
            $table->json('expenses')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'client_id']);
            $table->index(['company_id', 'start_time']);
            $table->index(['company_id', 'status']);
        });

        // Financial system tables
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color', 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
            $table->unique(['company_id', 'name']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained('expense_categories')->onDelete('restrict');
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->date('expense_date');
            $table->string('receipt_path')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_billable')->default(false);
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();

            $table->index(['company_id', 'expense_date']);
            $table->index(['company_id', 'category_id']);
            $table->index(['company_id', 'user_id']);
            $table->index(['company_id', 'is_billable']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();

            // Payment details
            $table->string('payment_method', 50);
            $table->string('payment_reference')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');

            // Gateway information
            $table->string('gateway', 50)->default('manual');
            $table->string('gateway_transaction_id')->nullable();
            $table->decimal('gateway_fee', 8, 2)->nullable();

            // Payment status and dates
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'cancelled',
                'refunded',
                'partial_refund',
                'chargeback',
            ])->default('pending');

            $table->timestamp('payment_date');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            // Refund and chargeback tracking
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->text('refund_reason')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->decimal('chargeback_amount', 10, 2)->nullable();
            $table->text('chargeback_reason')->nullable();
            $table->timestamp('chargeback_date')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['client_id', 'status']);
            $table->index(['invoice_id']);
            $table->index(['payment_date', 'company_id']);
            $table->index(['gateway', 'gateway_transaction_id']);
            $table->index(['payment_reference']);
            $table->index(['processed_by']);
        });

        // Tags system
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->integer('type')->default(1); // 1=Client, 2=Ticket, 3=Asset, 4=Document
            $table->string('color', 7)->nullable();
            $table->string('icon', 50)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();

            $table->unique(['company_id', 'name']);
            $table->index(['company_id', 'name']);
            $table->index('type');
            $table->index('archived_at');
        });

        Schema::create('client_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['client_id', 'tag_id']);
            $table->index(['company_id', 'client_id']);
            $table->index(['company_id', 'tag_id']);
        });

        // Quote templates table
        Schema::create('quote_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->enum('category', [
                'basic',
                'standard',
                'premium',
                'enterprise',
                'custom',
                'equipment',
                'maintenance',
                'professional',
                'managed',
            ]);
            $table->json('template_items')->nullable(); // Predefined line items
            $table->json('service_config')->nullable(); // Service-specific configuration
            $table->json('pricing_config')->nullable(); // Pricing structure
            $table->json('tax_config')->nullable(); // Tax configuration
            $table->text('terms_conditions')->nullable(); // Default terms and conditions
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();

            // Indexes for performance
            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'category']);
            $table->index('name');
            $table->index('category');
            $table->index('created_by');
            $table->unique(['company_id', 'name']); // Unique template names per company
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_templates');
        Schema::dropIfExists('client_tags');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('client_trips');
        Schema::dropIfExists('client_quotes');
        Schema::dropIfExists('client_recurring_invoices');
        Schema::dropIfExists('client_calendar_events');
        Schema::dropIfExists('client_domains');
        Schema::dropIfExists('client_racks');
        Schema::dropIfExists('client_certificates');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('sessions');
    }
};
