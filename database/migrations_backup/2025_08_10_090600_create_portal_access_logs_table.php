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
        Schema::create('portal_access_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('session_id')->nullable();
            $table->string('event_type'); // login, logout, page_view, action, error, security_event
            $table->string('action')->nullable(); // Specific action performed
            $table->string('resource')->nullable(); // Resource accessed (invoice, payment, etc.)
            $table->unsignedBigInteger('resource_id')->nullable(); // ID of accessed resource
            $table->string('method')->nullable(); // HTTP method (GET, POST, PUT, DELETE)
            $table->string('url', 500); // Full URL accessed
            $table->string('route_name')->nullable(); // Laravel route name
            $table->json('route_parameters')->nullable(); // Route parameters
            $table->json('request_data')->nullable(); // Request payload (sanitized)
            $table->json('response_data')->nullable(); // Response data (sanitized)
            $table->integer('response_status')->nullable(); // HTTP response code
            $table->decimal('response_time', 8, 3)->nullable(); // Response time in seconds
            
            // Client Information
            $table->string('ip_address', 45);
            $table->string('user_agent', 500)->nullable();
            $table->string('referer', 500)->nullable();
            $table->string('device_type')->nullable(); // desktop, tablet, mobile
            $table->string('browser_name')->nullable();
            $table->string('browser_version')->nullable();
            $table->string('os_name')->nullable();
            $table->string('os_version')->nullable();
            $table->boolean('is_mobile')->default(false);
            $table->boolean('is_bot')->default(false);
            
            // Geolocation
            $table->string('country', 2)->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->string('timezone')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('isp')->nullable();
            
            // Security Context
            $table->boolean('authenticated')->default(false);
            $table->boolean('two_factor_verified')->default(false);
            $table->boolean('trusted_device')->default(false);
            $table->string('security_level')->nullable(); // low, medium, high
            $table->json('security_flags')->nullable(); // Security-related flags
            $table->boolean('suspicious_activity')->default(false);
            $table->json('risk_factors')->nullable(); // Risk assessment factors
            $table->integer('risk_score')->nullable(); // 0-100 risk score
            
            // Error Information
            $table->boolean('is_error')->default(false);
            $table->string('error_type')->nullable(); // 404, 500, validation, authentication, etc.
            $table->text('error_message')->nullable();
            $table->json('error_details')->nullable();
            $table->text('stack_trace')->nullable();
            
            // Performance Metrics
            $table->decimal('memory_usage', 10, 2)->nullable(); // MB
            $table->decimal('cpu_time', 8, 3)->nullable(); // Seconds
            $table->integer('database_queries')->nullable(); // Number of DB queries
            $table->decimal('database_time', 8, 3)->nullable(); // Total DB time in seconds
            $table->json('performance_metrics')->nullable(); // Additional performance data
            
            // Business Context
            $table->string('feature_used')->nullable(); // Which portal feature was used
            $table->string('workflow_step')->nullable(); // Step in a business workflow
            $table->string('campaign_source')->nullable(); // Marketing campaign source
            $table->string('utm_source')->nullable(); // UTM tracking
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            
            // Session Context
            $table->integer('session_duration')->nullable(); // Session duration in seconds
            $table->integer('page_sequence')->nullable(); // Page number in session
            $table->integer('pages_viewed')->nullable(); // Total pages viewed in session
            $table->boolean('session_start')->default(false);
            $table->boolean('session_end')->default(false);
            $table->timestamp('session_started_at')->nullable();
            
            // Compliance and Audit
            $table->boolean('contains_pii')->default(false); // Contains personally identifiable info
            $table->boolean('gdpr_relevant')->default(false); // GDPR compliance relevant
            $table->string('data_classification')->nullable(); // public, internal, confidential, restricted
            $table->json('compliance_tags')->nullable(); // Compliance-related tags
            $table->boolean('audit_required')->default(false); // Requires detailed auditing
            
            // Rate Limiting and Throttling
            $table->integer('requests_per_minute')->nullable(); // Current rate
            $table->integer('rate_limit_exceeded')->nullable(); // Times rate limit was exceeded
            $table->boolean('throttled')->default(false); // Request was throttled
            $table->integer('throttle_delay')->nullable(); // Throttle delay in ms
            
            // A/B Testing and Experiments
            $table->string('experiment_id')->nullable(); // A/B test experiment ID
            $table->string('variant')->nullable(); // A/B test variant
            $table->json('experiment_data')->nullable(); // A/B test data
            $table->boolean('conversion_event')->default(false); // This was a conversion
            
            // Integration and External Services
            $table->json('external_service_calls')->nullable(); // External APIs called
            $table->decimal('external_service_time', 8, 3)->nullable(); // Time spent on external calls
            $table->json('webhook_triggers')->nullable(); // Webhooks triggered
            $table->string('api_version')->nullable(); // API version used
            
            // Custom Fields and Metadata
            $table->json('custom_fields')->nullable(); // Custom tracking fields
            $table->json('metadata')->nullable(); // Additional metadata
            $table->json('tags')->nullable(); // Custom tags for categorization
            $table->text('notes')->nullable(); // Internal notes
            
            // Aggregation and Reporting
            $table->date('log_date'); // Date for efficient querying
            $table->integer('log_hour'); // Hour (0-23) for time-based analysis
            $table->string('log_week'); // Week identifier (YYYY-WW)
            $table->string('log_month'); // Month identifier (YYYY-MM)
            $table->string('log_quarter'); // Quarter identifier (YYYY-Q)
            $table->integer('log_year'); // Year for annual reporting
            
            $table->timestamps();

            // Indexes for performance and analytics
            $table->index('company_id');
            $table->index('client_id');
            $table->index('session_id');
            $table->index('event_type');
            $table->index('action');
            $table->index('resource');
            $table->index('resource_id');
            $table->index('ip_address');
            $table->index('authenticated');
            $table->index('is_error');
            $table->index('error_type');
            $table->index('suspicious_activity');
            $table->index('risk_score');
            $table->index('feature_used');
            $table->index('log_date');
            $table->index('log_hour');
            $table->index('log_month');
            $table->index('created_at');
            $table->index(['company_id', 'client_id']);
            $table->index(['client_id', 'event_type']);
            $table->index(['client_id', 'log_date']);
            $table->index(['event_type', 'log_date']);
            $table->index(['is_error', 'log_date']);
            $table->index(['suspicious_activity', 'log_date']);
            $table->index(['authenticated', 'event_type']);
            $table->index(['log_date', 'log_hour']);
            $table->index(['resource', 'resource_id']);
            $table->index(['experiment_id', 'variant']);

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('session_id')->references('id')->on('client_portal_sessions')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portal_access_logs');
    }
};