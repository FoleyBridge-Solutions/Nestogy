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
            $table->json('hourly_rate_config')->nullable();
            $table->decimal('default_standard_rate', 10, 2)->default(150.00);
            $table->decimal('default_after_hours_rate', 10, 2)->default(225.00);
            $table->decimal('default_emergency_rate', 10, 2)->default(300.00);
            $table->decimal('default_weekend_rate', 10, 2)->default(200.00);
            $table->decimal('default_holiday_rate', 10, 2)->default(250.00);
            $table->decimal('after_hours_multiplier', 5, 2)->default(1.5);
            $table->decimal('emergency_multiplier', 5, 2)->default(2.0);
            $table->decimal('weekend_multiplier', 5, 2)->default(1.5);
            $table->decimal('holiday_multiplier', 5, 2)->default(2.0);
            $table->enum('rate_calculation_method', ['fixed_rates', 'multipliers'])->default('fixed_rates');
            $table->decimal('minimum_billing_increment', 5, 2)->default(0.25);
            $table->enum('time_rounding_method', ['none', 'up', 'down', 'nearest'])->default('nearest');
            $table->unsignedBigInteger('parent_company_id')->nullable();
            $table->enum('company_type', ['root', 'subsidiary', 'division'])->default('root');
            $table->unsignedInteger('organizational_level')->default(0);
            $table->json('subsidiary_settings')->nullable();
            $table->enum('access_level', ['full', 'limited', 'read_only'])->default('full');
            $table->enum('billing_type', ['independent', 'parent_billed', 'shared'])->default('independent');
            $table->unsignedBigInteger('billing_parent_id')->nullable();
            $table->boolean('can_create_subsidiaries')->default(false);
            $table->unsignedInteger('max_subsidiary_depth')->default(3);
            $table->json('inherited_permissions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('suspended_at')->nullable();
            $table->unsignedBigInteger('client_record_id')->nullable();
            $table->string('suspension_reason')->nullable();
            $table->enum('email_provider_type', ['manual', 'microsoft365', 'google_workspace', 'exchange', 'custom_oauth'])
                ->default('manual')
                ;
            $table->json('email_provider_config')->nullable();
            $table->string('size')->nullable()->comment('solo, small, medium, large, enterprise');
            $table->integer('employee_count')->nullable();
            $table->json('branding')->nullable();
            $table->json('company_info')->nullable();
            $table->json('social_links')->nullable();

            // Indexes
            $table->index('name');
            $table->index('email');
            $table->index('currency');
        
            $table->index(['parent_company_id', 'company_type'], 'companies_hierarchy_idx');
            $table->index(['organizational_level'], 'companies_level_idx');
            $table->index(['billing_parent_id'], 'companies_billing_idx');});

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
            $table->string('phone')->nullable();
            $table->string('title')->nullable();
            $table->string('department')->nullable();
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
            $table->string('theme', 20)->default('light');
            $table->json('preferences')->nullable();

            // Indexes
            $table->index('user_id');
            $table->index('company_id');
            $table->index('role');
            $table->index(['user_id', 'role']);
            $table->index(['company_id', 'role']);
        });

        // User roles pivot table (moved from 000002 to ensure users table exists)
        // Foreign key to roles table will be added in 000002 after roles table is created
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('company_id');
            $table->timestamps();

            $table->unique(['user_id', 'role_id', 'company_id']);
            $table->index(['user_id', 'company_id']);
            $table->index('role_id');
            
            // Add foreign key constraints for tables that exist now
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            // role_id foreign key will be added in 000002 migration
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->boolean('lead')->default(false);
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('type')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('country')->default('US');
            $table->string('website')->nullable();
            $table->string('referral')->nullable();
            $table->decimal('rate', 15, 2)->nullable();
            $table->string('currency_code', 3)->default('USD');
            $table->integer('net_terms')->default(30);
            $table->string('tax_id_number')->nullable();
            $table->integer('rmm_id')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->string('billing_contact')->nullable();
            $table->string('technical_contact')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamp('contract_start_date')->nullable();
            $table->timestamp('contract_end_date')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('sla_id')->nullable();
            $table->decimal('custom_standard_rate', 10, 2)->nullable();
            $table->decimal('custom_after_hours_rate', 10, 2)->nullable();
            $table->decimal('custom_emergency_rate', 10, 2)->nullable();
            $table->decimal('custom_weekend_rate', 10, 2)->nullable();
            $table->decimal('custom_holiday_rate', 10, 2)->nullable();
            $table->decimal('custom_after_hours_multiplier', 5, 2)->nullable();
            $table->decimal('custom_emergency_multiplier', 5, 2)->nullable();
            $table->decimal('custom_weekend_multiplier', 5, 2)->nullable();
            $table->decimal('custom_holiday_multiplier', 5, 2)->nullable();
            $table->enum('custom_rate_calculation_method', ['fixed_rates', 'multipliers'])->nullable();
            $table->decimal('custom_minimum_billing_increment', 5, 2)->nullable();
            $table->enum('custom_time_rounding_method', ['none', 'up', 'down', 'nearest'])->nullable();
            $table->boolean('use_custom_rates')->default(false);
            $table->unsignedBigInteger('company_link_id')->nullable();
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_subscription_id')->nullable();
            $table->string('subscription_status')->default('trialing');
            $table->unsignedBigInteger('subscription_plan_id')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('next_billing_date')->nullable();
            $table->timestamp('subscription_started_at')->nullable();
            $table->timestamp('subscription_canceled_at')->nullable();
            $table->integer('current_user_count')->default(0);
            $table->string('industry')->nullable();
            $table->integer('employee_count')->nullable();
            $table->softDeletes();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('accessed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->index(['company_id', 'sla_id']);

            $table->index(['status', 'created_at']);
            $table->index('company_name');
            $table->index('company_id');
            $table->index(['company_id', 'status']);
            $table->index('lead');
            $table->index('type');
            $table->index('accessed_at');
            $table->index(['company_id', 'lead']);
            $table->index(['company_id', 'archived_at']);
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

            // Portal access fields
            $table->boolean('has_portal_access')->default(false);
            $table->json('portal_permissions')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->integer('login_count')->default(0);
            $table->integer('failed_login_count')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->boolean('must_change_password')->default(false);
            $table->integer('session_timeout_minutes')->default(30);
            $table->json('allowed_ip_addresses')->nullable();

            $table->string('department')->nullable();
            $table->timestamps();
            $table->string('preferred_contact_method', 50)->nullable()->default('email');
            $table->string('best_time_to_contact', 50)->nullable()->default('anytime');
            $table->string('timezone', 100)->nullable();
            $table->string('language', 50)->nullable()->default('en');
            $table->boolean('do_not_disturb')->default(false);
            $table->boolean('marketing_opt_in')->default(false);
            $table->string('linkedin_url')->nullable();
            $table->string('assistant_name')->nullable();
            $table->string('assistant_email')->nullable();
            $table->string('assistant_phone', 50)->nullable();
            $table->unsignedBigInteger('reports_to_id')->nullable();
            $table->text('work_schedule')->nullable();
            $table->text('professional_bio')->nullable();
            $table->unsignedBigInteger('office_location_id')->nullable();
            $table->boolean('is_emergency_contact')->default(false);
            $table->boolean('is_after_hours_contact')->default(false);
            $table->date('out_of_office_start')->nullable();
            $table->date('out_of_office_end')->nullable();
            $table->string('website')->nullable();
            $table->string('twitter_handle', 100)->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('instagram_handle', 100)->nullable();
            $table->string('company_blog')->nullable();
            $table->string('role')->nullable();
            $table->timestamp('invitation_sent_at')->nullable()
                
                ->comment('When the invitation was sent');
            $table->timestamp('invitation_expires_at')->nullable()
                
                ->comment('When the invitation expires');
            $table->timestamp('invitation_accepted_at')->nullable()
                
                ->comment('When the invitation was accepted');
            $table->unsignedBigInteger('invitation_sent_by')->nullable()
                
                ->comment('User ID who sent the invitation');
            $table->enum('invitation_status', ['pending', 'sent', 'accepted', 'expired', 'revoked'])
                ->nullable()
                ->default(null)
                
                ->comment('Current status of the invitation');
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
            $table->index(['company_id', 'email', 'has_portal_access'], 'idx_portal_access');
            $table->index(['company_id', 'client_id', 'has_portal_access'], 'idx_client_portal');
        
            $table->index('preferred_contact_method');
            $table->index('timezone');
            $table->index('language');
            $table->index('is_emergency_contact');
            $table->index('is_after_hours_contact');
            $table->string('invitation_token', 64)->nullable()->unique()
                
                ->comment('Unique token for portal invitation');
            $table->index('invitation_token', 'idx_invitation_token');
            $table->index(['invitation_status', 'invitation_expires_at'], 'idx_invitation_status_expires');
            $table->index(['company_id', 'invitation_status'], 'idx_company_invitation_status');});

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
            $table->date('next_maintenance_date')
                ->nullable()
                
                ->comment('Date when the next scheduled maintenance is due for this asset');
            $table->string('support_level', 50)
                ->nullable()
                
                ->comment('Level of support: basic, standard, premium, enterprise, etc.');
            $table->boolean('auto_assigned_support')
                ->default(false)
                
                ->comment('Whether support was automatically assigned vs manually assigned');
            $table->timestamp('support_assigned_at')
                ->nullable()
                
                ->comment('When support was assigned to this asset');
            $table->unsignedBigInteger('support_assigned_by')
                ->nullable()
                
                ->comment('User who assigned support to this asset');
            $table->timestamp('support_last_evaluated_at')
                ->nullable()
                
                ->comment('When support status was last evaluated');
            $table->json('support_evaluation_rules')
                ->nullable()
                
                ->comment('Rules used to determine support status');
            $table->text('support_notes')
                ->nullable()
                
                ->comment('Notes about support assignment or exclusion reasons');
            $table->string('asset_tag')->nullable();
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
            $table->index('next_maintenance_date');
            $table->enum('support_status', ['supported', 'unsupported', 'pending_assignment', 'excluded'])
                ->default('unsupported')
                
                ->index()
                ->comment('Whether this asset is covered by a support contract');
            $table->unsignedBigInteger('supporting_contract_id')
                ->nullable()
                
                ->index()
                ->comment('Contract that provides support for this asset');
            $table->unsignedBigInteger('supporting_schedule_id')
                ->nullable()
                
                ->index()
                ->comment('Contract schedule (Schedule A) that defines asset support');
            $table->index(['company_id', 'support_status']);
            $table->index(['client_id', 'support_status']);
            $table->index(['supporting_contract_id', 'support_status']);
            $table->index(['support_status', 'type']);
            $table->index(['support_last_evaluated_at']);
            $table->index(['auto_assigned_support']);
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
            $table->string('status')->default('pending');
            $table->integer('progress')->default(0);
            $table->string('priority')->default('medium');
            $table->date('start_date')->nullable();
            $table->decimal('budget', 10, 2)->nullable();
            $table->decimal('actual_cost', 10, 2)->nullable();
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
            $table->timestamp('scheduled_at')->nullable();
            $table->boolean('onsite')->default(false);
            $table->string('vendor_ticket_number')->nullable();
            $table->string('feedback')->nullable();
            $table->timestamps();
            $table->decimal('sentiment_score', 3, 2)->nullable()->comment('Sentiment score from -1.00 (negative) to 1.00 (positive)');
            $table->enum('sentiment_label', ['POSITIVE', 'WEAK_POSITIVE', 'NEUTRAL', 'WEAK_NEGATIVE', 'NEGATIVE'])->nullable()->comment('Sentiment classification label');
            $table->timestamp('sentiment_analyzed_at')->nullable()->comment('When sentiment analysis was performed');
            $table->decimal('sentiment_confidence', 3, 2)->nullable()->comment('Confidence score for sentiment analysis (0.00 to 1.00)');
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->text('resolution_summary')->nullable();
            $table->boolean('client_can_reopen')->default(true);
            $table->timestamp('reopened_at')->nullable();
            $table->unsignedBigInteger('reopened_by')->nullable();
            $table->integer('resolution_count')->default(0);
            $table->string('type')->nullable();
            $table->timestamp('estimated_resolution_at')->nullable();
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
            $table->index('scheduled_at');
            $table->index('closed_at');
            $table->index('archived_at');
            $table->unique(['prefix', 'number']);
            $table->index(['sentiment_label', 'created_at'], 'idx_tickets_sentiment_created');
            $table->index(['sentiment_score', 'company_id'], 'idx_tickets_sentiment_company');
            $table->index('is_resolved');
            $table->index(['is_resolved', 'status']);
            $table->index('resolved_at');
            $table->index(['company_id', 'status'], 'idx_tickets_company_status');
            $table->index(['company_id', 'assigned_to', 'status'], 'idx_tickets_company_assigned_status');
            $table->index(['company_id', 'priority', 'status'], 'idx_tickets_company_priority_status');
            $table->index(['company_id', 'created_at'], 'idx_tickets_company_created');
            $table->index(['assigned_to', 'status'], 'idx_tickets_assigned_status');
            $table->index(['client_id', 'status'], 'idx_tickets_client_status');
            $table->index(['is_resolved', 'resolved_at'], 'idx_tickets_resolved');
            $table->index(['created_at', 'status'], 'idx_tickets_created_status');
        });

        Schema::create('ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->longText('reply');
            $table->string('type', 10); // public, private, internal
            $table->time('time_worked')->nullable();
            $table->timestamps();
            $table->decimal('sentiment_score', 3, 2)->nullable()->comment('Sentiment score from -1.00 (negative) to 1.00 (positive)');
            $table->enum('sentiment_label', ['POSITIVE', 'WEAK_POSITIVE', 'NEUTRAL', 'WEAK_NEGATIVE', 'NEGATIVE'])->nullable()->comment('Sentiment classification label');
            $table->timestamp('sentiment_analyzed_at')->nullable()->comment('When sentiment analysis was performed');
            $table->decimal('sentiment_confidence', 3, 2)->nullable()->comment('Confidence score for sentiment analysis (0.00 to 1.00)');
            $table->timestamp('archived_at')->nullable();
            $table->unsignedBigInteger('replied_by');
            $table->unsignedBigInteger('ticket_id');

            // Indexes
            $table->index('ticket_id');
            $table->index('company_id');
            $table->index('replied_by');
            $table->index('type');
            $table->index(['ticket_id', 'type']);
            $table->index(['sentiment_label', 'created_at'], 'idx_ticket_replies_sentiment_created');
            $table->index(['sentiment_score', 'ticket_id'], 'idx_ticket_replies_sentiment_ticket');
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
            $table->date('due_date');
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('amount', 15, 2)->default(0.00);
            $table->string('currency_code', 3);
            $table->text('note')->nullable();
            $table->string('url_key')->nullable(); // For public access
            $table->timestamps();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->unsignedBigInteger('recurring_invoice_id')->nullable();
            $table->string('recurring_frequency')->nullable()
                ->comment('monthly, quarterly, yearly, etc.');
            $table->date('next_recurring_date')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('client_id');

            // Indexes
            $table->index('number');
            $table->index('status');
            $table->index('client_id');
            $table->index('company_id');
            $table->index('date');
            $table->index('due_date');
            $table->index('contract_id');
            $table->index('is_recurring');
            $table->index('next_recurring_date');
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
            $table->json('tax_breakdown')->nullable();
            $table->json('service_data')->nullable();
            $table->decimal('tax_rate', 8, 4)->nullable();
            $table->string('service_type', 50)->nullable();
            $table->unsignedBigInteger('tax_jurisdiction_id')->nullable();
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
        
            $table->index('service_type');
            $table->index('tax_jurisdiction_id');});

        // Note: payments and expenses tables are created by separate comprehensive migrations
        // 2024_01_15_000003_create_payments_table.php
        // 2024_01_15_000002_create_expenses_table.php

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2);
            $table->integer('cost')->nullable();
            $table->string('currency_code', 3);
            $table->timestamps();
            $table->unsignedBigInteger('tax_profile_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedBigInteger('tax_id')->nullable();
            $table->unsignedBigInteger('category_id');

            // Indexes
            $table->index('name');
            $table->index('category_id');
            $table->index('company_id');
            $table->index('tax_profile_id');
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
            $table->enum('approval_status', [
                'pending',
                'manager_approved',
                'executive_approved',
                'rejected',
                'not_required',
            ])->default('not_required');
            $table->timestamp('sent_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('client_id');

            // Indexes
            $table->index('number');
            $table->index('status');
            $table->index('client_id');
            $table->index('company_id');
            $table->index(['company_id', 'status'], 'quotes_company_status_idx');
            $table->index('approval_status');
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
            $table->boolean('auto_invoice_generation')->default(true);
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
            $table->string('date_format', 20)->default('Y-m-d');
            $table->string('company_logo')->nullable();
            $table->json('company_colors')->nullable();
            $table->string('company_address')->nullable();
            $table->string('company_city')->nullable();
            $table->string('company_state')->nullable();
            $table->string('company_zip')->nullable();
            $table->string('company_country')->default('US');
            $table->string('company_phone')->nullable();
            $table->string('company_website')->nullable();
            $table->string('company_tax_id')->nullable();
            $table->json('business_hours')->nullable();
            $table->json('company_holidays')->nullable();
            $table->string('company_language')->default('en');
            $table->string('company_currency')->default('USD');
            $table->json('custom_fields')->nullable();
            $table->json('localization_settings')->nullable();
            $table->integer('password_min_length')->default(8);
            $table->boolean('password_require_special')->default(true);
            $table->boolean('password_require_numbers')->default(true);
            $table->boolean('password_require_uppercase')->default(true);
            $table->integer('password_expiry_days')->default(90);
            $table->integer('password_history_count')->default(5);
            $table->boolean('two_factor_enabled')->default(false);
            $table->json('two_factor_methods')->nullable();
            $table->integer('session_timeout_minutes')->default(480);
            $table->boolean('force_single_session')->default(false);
            $table->integer('max_login_attempts')->default(5);
            $table->integer('lockout_duration_minutes')->default(15);
            $table->json('allowed_ip_ranges')->nullable();
            $table->json('blocked_ip_ranges')->nullable();
            $table->boolean('geo_blocking_enabled')->default(false);
            $table->json('allowed_countries')->nullable();
            $table->json('sso_settings')->nullable();
            $table->boolean('audit_logging_enabled')->default(true);
            $table->integer('audit_retention_days')->default(365);
            $table->boolean('smtp_auth_required')->default(true);
            $table->boolean('smtp_use_tls')->default(true);
            $table->integer('smtp_timeout')->default(30);
            $table->integer('email_retry_attempts')->default(3);
            $table->json('email_templates')->nullable();
            $table->json('email_signatures')->nullable();
            $table->boolean('email_tracking_enabled')->default(false);
            $table->json('sms_settings')->nullable();
            $table->json('voice_settings')->nullable();
            $table->json('slack_settings')->nullable();
            $table->json('teams_settings')->nullable();
            $table->json('discord_settings')->nullable();
            $table->json('video_conferencing_settings')->nullable();
            $table->json('communication_preferences')->nullable();
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();
            $table->boolean('multi_currency_enabled')->default(false);
            $table->json('supported_currencies')->nullable();
            $table->string('exchange_rate_provider')->nullable();
            $table->boolean('auto_update_exchange_rates')->default(true);
            $table->json('tax_calculation_settings')->nullable();
            $table->string('tax_engine_provider')->nullable();
            $table->json('payment_gateway_settings')->nullable();
            $table->json('stripe_settings')->nullable();
            $table->json('square_settings')->nullable();
            $table->json('paypal_settings')->nullable();
            $table->json('authorize_net_settings')->nullable();
            $table->json('ach_settings')->nullable();
            $table->boolean('recurring_billing_enabled')->default(true);
            $table->json('recurring_billing_settings')->nullable();
            $table->json('late_fee_settings')->nullable();
            $table->json('collection_settings')->nullable();
            $table->json('accounting_integration_settings')->nullable();
            $table->json('quickbooks_settings')->nullable();
            $table->json('xero_settings')->nullable();
            $table->json('sage_settings')->nullable();
            $table->boolean('revenue_recognition_enabled')->default(false);
            $table->json('revenue_recognition_settings')->nullable();
            $table->json('purchase_order_settings')->nullable();
            $table->json('expense_approval_settings')->nullable();
            $table->json('connectwise_automate_settings')->nullable();
            $table->json('datto_rmm_settings')->nullable();
            $table->json('ninja_rmm_settings')->nullable();
            $table->json('kaseya_vsa_settings')->nullable();
            $table->json('auvik_settings')->nullable();
            $table->json('prtg_settings')->nullable();
            $table->json('solarwinds_settings')->nullable();
            $table->json('monitoring_alert_thresholds')->nullable();
            $table->json('escalation_rules')->nullable();
            $table->json('asset_discovery_settings')->nullable();
            $table->json('patch_management_settings')->nullable();
            $table->json('remote_access_settings')->nullable();
            $table->boolean('auto_create_tickets_from_alerts')->default(false);
            $table->json('alert_to_ticket_mapping')->nullable();
            $table->json('ticket_categorization_rules')->nullable();
            $table->json('ticket_priority_rules')->nullable();
            $table->json('sla_definitions')->nullable();
            $table->json('sla_escalation_policies')->nullable();
            $table->json('auto_assignment_rules')->nullable();
            $table->json('routing_logic')->nullable();
            $table->json('approval_workflows')->nullable();
            $table->boolean('time_tracking_enabled')->default(true);
            $table->json('time_tracking_settings')->nullable();
            $table->boolean('customer_satisfaction_enabled')->default(false);
            $table->json('csat_settings')->nullable();
            $table->json('ticket_templates')->nullable();
            $table->json('ticket_automation_rules')->nullable();
            $table->json('multichannel_settings')->nullable();
            $table->json('queue_management_settings')->nullable();
            $table->boolean('remember_me_enabled')->default(true);
            $table->json('wire_settings')->nullable();
            $table->json('check_settings')->nullable();
            $table->enum('imap_auth_method', ['password', 'oauth', 'token'])->nullable();

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

        // Note: Foreign keys for payments and expenses are handled in their respective comprehensive migrations

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
        // Note: expenses and payments tables are dropped by their respective comprehensive migrations
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
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('user_settings');
        Schema::dropIfExists('users');
        Schema::dropIfExists('companies');
    }
};
