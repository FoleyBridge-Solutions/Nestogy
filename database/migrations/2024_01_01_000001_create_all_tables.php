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
        // ========================================
        // PART 1: CREATE ALL TABLES WITHOUT FOREIGN KEYS
        // ========================================

        // Core system tables first
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->string('country')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('logo')->nullable();
            $table->string('locale')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->timestamps();

            // Indexes
            $table->index('name');
            $table->index('email');
            $table->index('currency');
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('status')->default(true);
            $table->string('token')->nullable();
            $table->string('avatar')->nullable();
            $table->string('specific_encryption_ciphertext')->nullable();
            $table->string('php_session')->nullable();
            $table->string('extension_key', 18)->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();

            // Indexes
            $table->index('email');
            $table->index('status');
            $table->index('company_id');
            $table->index(['email', 'status']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('company_id');
            $table->integer('role')->default(1); // 1=Accountant, 2=Tech, 3=Admin
            $table->string('remember_me_token')->nullable();
            $table->boolean('force_mfa')->default(false);
            $table->integer('records_per_page')->default(10);
            $table->boolean('dashboard_financial_enable')->default(false);
            $table->boolean('dashboard_technical_enable')->default(false);
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('company_id');
            $table->index('role');
            $table->index(['user_id', 'role']);
            $table->index(['company_id', 'role']);
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('country')->default('US');
            $table->string('website')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->string('billing_contact')->nullable();
            $table->string('technical_contact')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamp('contract_start_date')->nullable();
            $table->timestamp('contract_end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'created_at']);
            $table->index('company_name');
            $table->index('company_id');
            $table->index(['company_id', 'status']);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->string('type'); // expense, income, ticket, etc.
            $table->string('color')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();

            // Indexes
            $table->index('name');
            $table->index('type');
            $table->index('parent_id');
            $table->index('company_id');
            $table->index(['type', 'parent_id']);
            $table->index(['company_id', 'type']);
            $table->index('archived_at');
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('extension')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('hours')->nullable();
            $table->string('sla')->nullable();
            $table->string('code')->nullable();
            $table->string('account_number')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('template')->default(false);
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('accessed_at')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();

            // Indexes
            $table->index('name');
            $table->index('email');
            $table->index('client_id');
            $table->index('company_id');
            $table->index('template');
            $table->index(['company_id', 'template']);
            $table->index('archived_at');
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('extension')->nullable();
            $table->string('mobile')->nullable();
            $table->string('photo')->nullable();
            $table->string('pin')->nullable();
            $table->text('notes')->nullable();
            $table->string('auth_method')->nullable();
            $table->string('password_hash')->nullable();
            $table->string('password_reset_token')->nullable();
            $table->timestamp('token_expire')->nullable();
            $table->boolean('primary')->default(false);
            $table->boolean('important')->default(false);
            $table->boolean('billing')->default(false);
            $table->boolean('technical')->default(false);
            $table->string('department')->nullable();
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('accessed_at')->nullable();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();

            // Indexes
            $table->index('name');
            $table->index('email');
            $table->index('client_id');
            $table->index('company_id');
            $table->index('primary');
            $table->index('important');
            $table->index(['client_id', 'primary']);
            $table->index(['client_id', 'billing']);
            $table->index(['client_id', 'technical']);
            $table->index(['company_id', 'client_id']);
            $table->index('archived_at');
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('country')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->string('phone')->nullable();
            $table->string('hours')->nullable();
            $table->string('photo')->nullable();
            $table->boolean('primary')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('accessed_at')->nullable();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('contact_id')->nullable();

            // Indexes
            $table->index('name');
            $table->index('client_id');
            $table->index('company_id');
            $table->index('primary');
            $table->index(['client_id', 'primary']);
            $table->index(['company_id', 'client_id']);
            $table->index('archived_at');
        });

        Schema::create('networks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('vlan')->nullable();
            $table->string('network'); // CIDR notation
            $table->string('gateway');
            $table->string('dhcp_range')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('accessed_at')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('client_id');

            // Indexes
            $table->index('name');
            $table->index('client_id');
            $table->index('company_id');
            $table->index('location_id');
            $table->index('vlan');
            $table->index(['company_id', 'client_id']);
            $table->index('archived_at');
        });

        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('type'); // Server, Workstation, Laptop, etc.
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('make');
            $table->string('model')->nullable();
            $table->string('serial')->nullable();
            $table->string('os')->nullable();
            $table->string('ip', 45)->nullable(); // Support IPv6
            $table->string('nat_ip')->nullable();
            $table->string('mac', 17)->nullable();
            $table->string('uri', 500)->nullable();
            $table->string('uri_2', 500)->nullable();
            $table->string('status')->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('warranty_expire')->nullable();
            $table->date('install_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('accessed_at')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('network_id')->nullable();
            $table->unsignedBigInteger('client_id');
            $table->string('rmm_id')->nullable(); // Remote Monitoring & Management ID

            // Indexes
            $table->index('name');
            $table->index('type');
            $table->index('client_id');
            $table->index('company_id');
            $table->index('status');
            $table->index('ip');
            $table->index('mac');
            $table->index('serial');
            $table->index(['client_id', 'type']);
            $table->index(['client_id', 'status']);
            $table->index(['company_id', 'client_id']);
            $table->index('warranty_expire');
            $table->index('archived_at');
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('prefix')->nullable();
            $table->integer('number')->default(1);
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('due')->nullable();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->timestamps();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedBigInteger('client_id');

            // Indexes
            $table->index('name');
            $table->index('client_id');
            $table->index('company_id');
            $table->index('manager_id');
            $table->index('due');
            $table->index('completed_at');
            $table->index(['company_id', 'client_id']);
            $table->index('archived_at');
            $table->unique(['prefix', 'number']);
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('prefix')->nullable();
            $table->integer('number');
            $table->string('source')->nullable(); // Email, Phone, Portal, etc.
            $table->string('category')->nullable();
            $table->string('subject');
            $table->longText('details');
            $table->string('priority')->nullable(); // Low, Normal, High, Critical
            $table->string('status'); // Open, In Progress, Resolved, Closed
            $table->boolean('billable')->default(false);
            $table->timestamp('schedule')->nullable();
            $table->boolean('onsite')->default(false);
            $table->string('vendor_ticket_number')->nullable();
            $table->string('feedback')->nullable();
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('asset_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();

            // Indexes
            $table->index('number');
            $table->index('status');
            $table->index('priority');
            $table->index('client_id');
            $table->index('company_id');
            $table->index('assigned_to');
            $table->index('created_by');
            $table->index(['client_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index(['company_id', 'client_id']);
            $table->index('billable');
            $table->index('schedule');
            $table->index('closed_at');
            $table->index('archived_at');
            $table->unique(['prefix', 'number']);
        });

        Schema::create('ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->longText('reply');
            $table->string('type', 10); // public, private, internal
            $table->time('time_worked')->nullable();
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedBigInteger('replied_by');
            $table->unsignedBigInteger('ticket_id');

            // Indexes
            $table->index('ticket_id');
            $table->index('company_id');
            $table->index('replied_by');
            $table->index('type');
            $table->index(['ticket_id', 'type']);
            $table->index(['company_id', 'ticket_id']);
            $table->index('time_worked');
            $table->index('archived_at');
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->decimal('opening_balance', 15, 2)->default(0.00);
            $table->string('currency_code', 3);
            $table->text('notes')->nullable();
            $table->integer('type')->nullable(); // Account type reference
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            $table->string('plaid_id')->nullable(); // For bank integration

            // Indexes
            $table->index('name');
            $table->index('company_id');
            $table->index('currency_code');
            $table->index('type');
            $table->index(['company_id', 'type']);
            $table->index('archived_at');
        });

        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->float('percent');
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();

            // Indexes
            $table->index('name');
            $table->index('company_id');
            $table->index('percent');
            $table->index(['company_id', 'name']);
            $table->index('archived_at');
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('prefix')->nullable();
            $table->integer('number');
            $table->string('scope')->nullable();
            $table->string('status'); // Draft, Sent, Paid, Overdue, Cancelled
            $table->date('date');
            $table->date('due');
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('amount', 15, 2)->default(0.00);
            $table->string('currency_code', 3);
            $table->text('note')->nullable();
            $table->string('url_key')->nullable(); // For public access
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('client_id');

            // Indexes
            $table->index('number');
            $table->index('status');
            $table->index('client_id');
            $table->index('company_id');
            $table->index('date');
            $table->index('due');
            $table->index(['client_id', 'status']);
            $table->index(['company_id', 'client_id']);
            $table->index('url_key');
            $table->index('archived_at');
            $table->unique(['prefix', 'number']);
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 15, 2)->default(0.00);
            $table->decimal('price', 15, 2)->default(0.00);
            $table->decimal('discount', 15, 2)->default(0.00);
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('tax', 15, 2)->default(0.00);
            $table->decimal('total', 15, 2)->default(0.00);
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedBigInteger('tax_id')->nullable();
            $table->unsignedBigInteger('quote_id')->nullable();
            $table->unsignedBigInteger('recurring_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();

            // Indexes
            $table->index('invoice_id');
            $table->index('company_id');
            $table->index('order');
            $table->index(['company_id', 'invoice_id']);
            $table->index('archived_at');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->date('date');
            $table->decimal('amount', 15, 2);
            $table->string('currency_code', 3);
            $table->string('method')->nullable(); // Cash, Check, Credit Card, Bank Transfer, etc.
            $table->string('reference')->nullable(); // Check number, transaction ID, etc.
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('plaid_transaction_id')->nullable(); // For bank integration

            // Indexes
            $table->index('date');
            $table->index('invoice_id');
            $table->index('account_id');
            $table->index('company_id');
            $table->index('method');
            $table->index(['invoice_id', 'date']);
            $table->index(['company_id', 'date']);
            $table->index('archived_at');
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('currency_code', 3);
            $table->date('date');
            $table->string('reference')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('receipt')->nullable(); // File path to receipt
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('plaid_transaction_id')->nullable(); // For bank integration

            // Indexes
            $table->index('date');
            $table->index('vendor_id');
            $table->index('client_id');
            $table->index('category_id');
            $table->index('account_id');
            $table->index('company_id');
            $table->index(['client_id', 'date']);
            $table->index(['category_id', 'date']);
            $table->index(['company_id', 'date']);
            $table->index('archived_at');
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2);
            $table->integer('cost')->nullable();
            $table->string('currency_code', 3);
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedBigInteger('tax_id')->nullable();
            $table->unsignedBigInteger('category_id');

            // Indexes
            $table->index('name');
            $table->index('category_id');
            $table->index('company_id');
            $table->index('price');
            $table->index(['company_id', 'category_id']);
            $table->index('archived_at');
        });

        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('prefix')->nullable();
            $table->integer('number');
            $table->string('scope')->nullable();
            $table->string('status'); // Draft, Sent, Accepted, Declined, Expired
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->date('date');
            $table->date('expire')->nullable();
            $table->decimal('amount', 15, 2)->default(0.00);
            $table->string('currency_code', 3);
            $table->text('note')->nullable();
            $table->string('url_key')->nullable(); // For public access
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('client_id');

            // Indexes
            $table->index('number');
            $table->index('status');
            $table->index('client_id');
            $table->index('company_id');
            $table->index('date');
            $table->index('expire');
            $table->index(['client_id', 'status']);
            $table->index(['company_id', 'client_id']);
            $table->index('url_key');
            $table->index('archived_at');
            $table->unique(['prefix', 'number']);
        });

        Schema::create('recurring', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('prefix')->nullable();
            $table->integer('number');
            $table->string('scope')->nullable();
            $table->string('frequency'); // Monthly, Quarterly, Yearly, etc.
            $table->date('last_sent')->nullable();
            $table->date('next_date');
            $table->boolean('status')->default(true);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('amount', 15, 2)->default(0.00);
            $table->string('currency_code', 3);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('client_id');

            // Indexes
            $table->index('number');
            $table->index('status');
            $table->index('client_id');
            $table->index('company_id');
            $table->index('frequency');
            $table->index('next_date');
            $table->index(['client_id', 'status']);
            $table->index(['status', 'next_date']);
            $table->index(['company_id', 'client_id']);
            $table->index('archived_at');
            $table->unique(['prefix', 'number']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('event_type', 50); // login, logout, create, update, delete, security, api
            $table->string('model_type')->nullable(); // Model class name
            $table->unsignedBigInteger('model_id')->nullable(); // Model ID
            $table->string('action'); // Specific action performed
            $table->json('old_values')->nullable(); // Previous values for updates
            $table->json('new_values')->nullable(); // New values for creates/updates
            $table->json('metadata')->nullable(); // Additional context data
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->string('request_method', 10)->nullable();
            $table->string('request_url')->nullable();
            $table->json('request_headers')->nullable();
            $table->json('request_body')->nullable();
            $table->integer('response_status')->nullable();
            $table->decimal('execution_time', 10, 3)->nullable(); // in seconds
            $table->string('severity', 20)->default('info'); // info, warning, error, critical
            $table->timestamps();
            
            // Indexes for performance
            $table->index('user_id');
            $table->index('company_id');
            $table->index('event_type');
            $table->index('model_type');
            $table->index('model_id');
            $table->index('created_at');
            $table->index('ip_address');
            $table->index('severity');
            $table->index(['model_type', 'model_id']);
            $table->index(['company_id', 'event_type']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('current_database_version', 10);
            $table->string('start_page')->default('clients.php');
            
            // SMTP Configuration
            $table->string('smtp_host')->nullable();
            $table->integer('smtp_port')->nullable();
            $table->string('smtp_encryption')->nullable();
            $table->string('smtp_username')->nullable();
            $table->string('smtp_password')->nullable();
            $table->string('mail_from_email')->nullable();
            $table->string('mail_from_name')->nullable();
            
            // IMAP Configuration
            $table->string('imap_host')->nullable();
            $table->integer('imap_port')->nullable();
            $table->string('imap_encryption')->nullable();
            $table->string('imap_username')->nullable();
            $table->string('imap_password')->nullable();
            
            // Default Account Settings
            $table->integer('default_transfer_from_account')->nullable();
            $table->integer('default_transfer_to_account')->nullable();
            $table->integer('default_payment_account')->nullable();
            $table->integer('default_expense_account')->nullable();
            $table->string('default_payment_method')->nullable();
            $table->string('default_expense_payment_method')->nullable();
            $table->integer('default_calendar')->nullable();
            $table->integer('default_net_terms')->nullable();
            $table->decimal('default_hourly_rate', 15, 2)->default(0.00);
            
            // Invoice Settings
            $table->string('invoice_prefix')->nullable();
            $table->integer('invoice_next_number')->nullable();
            $table->text('invoice_footer')->nullable();
            $table->string('invoice_from_name')->nullable();
            $table->string('invoice_from_email')->nullable();
            $table->boolean('invoice_late_fee_enable')->default(false);
            $table->decimal('invoice_late_fee_percent', 5, 2)->default(0.00);
            
            // Quote Settings
            $table->string('quote_prefix')->nullable();
            $table->integer('quote_next_number')->nullable();
            $table->text('quote_footer')->nullable();
            $table->string('quote_from_name')->nullable();
            $table->string('quote_from_email')->nullable();
            
            // Ticket Settings
            $table->string('ticket_prefix')->nullable();
            $table->integer('ticket_next_number')->nullable();
            $table->string('ticket_from_name')->nullable();
            $table->string('ticket_from_email')->nullable();
            $table->boolean('ticket_email_parse')->default(false);
            $table->boolean('ticket_client_general_notifications')->default(true);
            $table->boolean('ticket_autoclose')->default(false);
            $table->integer('ticket_autoclose_hours')->default(72);
            $table->string('ticket_new_ticket_notification_email')->nullable();
            
            // System Settings
            $table->boolean('enable_cron')->default(false);
            $table->string('cron_key')->nullable();
            $table->boolean('recurring_auto_send_invoice')->default(true);
            $table->boolean('enable_alert_domain_expire')->default(true);
            $table->boolean('send_invoice_reminders')->default(true);
            $table->string('invoice_overdue_reminders')->nullable();
            $table->string('theme')->default('blue');
            $table->boolean('telemetry')->default(false);
            $table->string('timezone')->default('America/New_York');
            $table->boolean('destructive_deletes_enable')->default(false);
            
            // Module Settings
            $table->boolean('module_enable_itdoc')->default(true);
            $table->boolean('module_enable_accounting')->default(true);
            $table->boolean('module_enable_ticketing')->default(true);
            $table->boolean('client_portal_enable')->default(true);
            
            // Security Settings
            $table->text('login_message')->nullable();
            $table->boolean('login_key_required')->default(false);
            $table->string('login_key_secret')->nullable();
            
            $table->timestamps();

            // Indexes
            $table->index('company_id');
            $table->unique('company_id'); // One settings record per company
        });

        // Media table for file attachments (Spatie Media Library)
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->morphs('model');
            $table->uuid()->nullable()->unique();
            $table->string('collection_name');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size');
            $table->json('manipulations');
            $table->json('custom_properties');
            $table->json('generated_conversions');
            $table->json('responsive_images');
            $table->unsignedInteger('order_column')->nullable()->index();
            $table->nullableTimestamps();
        });

        // ========================================
        // PART 2: ADD ALL FOREIGN KEY CONSTRAINTS
        // ========================================

        // Users foreign keys
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // User settings foreign keys
        Schema::table('user_settings', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // Clients foreign keys
        Schema::table('clients', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // Categories foreign keys
        Schema::table('categories', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('categories')->onDelete('cascade');
        });

        // Vendors foreign keys
        Schema::table('vendors', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('template_id')->references('id')->on('vendors')->onDelete('set null');
        });

        // Contacts foreign keys
        Schema::table('contacts', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('set null');
        });

        // Locations foreign keys
        Schema::table('locations', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
        });

        // Networks foreign keys
        Schema::table('networks', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });

        // Assets foreign keys
        Schema::table('assets', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('set null');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
            $table->foreign('network_id')->references('id')->on('networks')->onDelete('set null');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });

        // Projects foreign keys
        Schema::table('projects', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('manager_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });

        // Tickets foreign keys
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('closed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('set null');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('set null');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('set null');
        });

        // Ticket replies foreign keys
        Schema::table('ticket_replies', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('replied_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
        });

        // Accounts foreign keys
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // Taxes foreign keys
        Schema::table('taxes', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // Invoices foreign keys
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });

        // Invoice items foreign keys
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('tax_id')->references('id')->on('taxes')->onDelete('set null');
            $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('set null');
            $table->foreign('recurring_id')->references('id')->on('recurring')->onDelete('set null');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
        });

        // Payments foreign keys
        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
        });

        // Expenses foreign keys
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('set null');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('set null');
        });

        // Products foreign keys
        Schema::table('products', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('tax_id')->references('id')->on('taxes')->onDelete('set null');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });

        // Quotes foreign keys
        Schema::table('quotes', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });

        // Recurring foreign keys
        Schema::table('recurring', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });

        // Audit logs foreign keys
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
        });

        // Settings foreign keys
        Schema::table('settings', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // Add invoice_id foreign key to tickets (after invoices table exists)
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop tables in reverse order to avoid foreign key constraint issues
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('media');
        Schema::dropIfExists('recurring');
        Schema::dropIfExists('quotes');
        Schema::dropIfExists('products');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('taxes');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('ticket_replies');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('networks');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('user_settings');
        Schema::dropIfExists('users');
        Schema::dropIfExists('companies');
    }
};