<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration for Usage Alerts Table
 * 
 * Manages usage threshold monitoring and alerting system for real-time 
 * usage tracking with predictive analytics and automated notifications.
 */
class CreateUsageAlertsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usage_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('client_id')->index();
            $table->unsignedBigInteger('usage_pool_id')->nullable()->index();
            $table->unsignedBigInteger('usage_bucket_id')->nullable()->index();

            // Alert Configuration
            $table->string('alert_name', 100)->comment('Descriptive name for the alert');
            $table->string('alert_code', 50)->index()->comment('Unique alert identifier');
            $table->string('alert_type', 30)->comment('threshold, usage_pattern, anomaly, predictive, billing');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();

            // Usage Monitoring Configuration
            $table->string('usage_type', 50)->index()->comment('voice, data, sms, mms, feature, equipment, api');
            $table->json('service_types')->nullable()->comment('Specific services to monitor');
            $table->string('monitoring_scope', 30)->comment('client, pool, bucket, service, global');
            $table->string('measurement_period', 20)->comment('daily, weekly, monthly, billing_cycle, real_time');

            // Threshold Configuration
            $table->string('threshold_type', 30)->comment('percentage, absolute, rate_of_change, predictive');
            $table->decimal('threshold_value', 12, 4)->comment('Alert threshold value');
            $table->string('threshold_unit', 20)->comment('percent, minutes, mb, gb, dollars, calls');
            $table->decimal('critical_threshold', 12, 4)->nullable()->comment('Critical alert threshold');
            $table->decimal('warning_threshold', 12, 4)->nullable()->comment('Warning alert threshold');

            // Alert Conditions and Logic
            $table->string('comparison_operator', 10)->default('>=')->comment('>=, >, <=, <, =, !=');
            $table->json('alert_conditions')->nullable()->comment('Complex conditional logic');
            $table->json('trigger_criteria')->nullable()->comment('Additional trigger criteria');
            $table->boolean('require_consecutive_periods')->default(false)->comment('Require multiple periods to trigger');
            $table->integer('consecutive_period_count')->default(1);

            // Predictive Analytics Configuration
            $table->boolean('enable_predictive_alerts')->default(false);
            $table->integer('prediction_horizon_hours')->default(24)->comment('Hours ahead to predict');
            $table->decimal('prediction_confidence_threshold', 5, 2)->default(80)->comment('Minimum confidence percentage');
            $table->json('prediction_model_params')->nullable()->comment('Prediction model parameters');
            $table->string('prediction_algorithm', 30)->nullable()->comment('linear, exponential, ml_model');

            // Current Status and Metrics
            $table->decimal('current_usage', 15, 4)->default(0)->comment('Current usage value');
            $table->decimal('current_threshold_percentage', 5, 2)->default(0)->comment('Current threshold percentage');
            $table->string('alert_status', 20)->default('normal')->index()->comment('normal, warning, critical, triggered');
            $table->timestamp('last_check_at')->nullable();
            $table->timestamp('last_triggered_at')->nullable();
            $table->integer('trigger_count')->default(0)->comment('Number of times alert has triggered');

            // Escalation and Severity Management
            $table->string('severity_level', 20)->default('medium')->comment('low, medium, high, critical');
            $table->boolean('enable_escalation')->default(false);
            $table->json('escalation_rules')->nullable()->comment('Escalation configuration');
            $table->integer('escalation_delay_minutes')->default(60);
            $table->timestamp('last_escalated_at')->nullable();
            $table->integer('escalation_level')->default(0);

            // Notification Configuration
            $table->json('notification_methods')->nullable()->comment('email, sms, webhook, dashboard');
            $table->json('notification_recipients')->nullable()->comment('Who gets notified');
            $table->boolean('email_notifications')->default(true);
            $table->boolean('sms_notifications')->default(false);
            $table->boolean('webhook_notifications')->default(false);
            $table->boolean('dashboard_notifications')->default(true);

            // Email Notification Settings
            $table->string('email_template', 100)->nullable()->comment('Email template to use');
            $table->json('email_recipients')->nullable()->comment('Email addresses');
            $table->string('email_subject_template', 255)->nullable();
            $table->boolean('email_include_charts')->default(true);
            $table->boolean('email_include_recommendations')->default(true);

            // SMS Notification Settings
            $table->json('sms_recipients')->nullable()->comment('Phone numbers for SMS');
            $table->string('sms_message_template', 500)->nullable();
            $table->boolean('sms_only_critical')->default(true);

            // Webhook Configuration
            $table->string('webhook_url', 500)->nullable();
            $table->string('webhook_method', 10)->default('POST');
            $table->json('webhook_headers')->nullable();
            $table->json('webhook_payload_template')->nullable();
            $table->integer('webhook_timeout_seconds')->default(30);
            $table->integer('webhook_retry_attempts')->default(3);

            // Suppression and Rate Limiting
            $table->boolean('enable_suppression')->default(true);
            $table->integer('suppression_window_minutes')->default(60)->comment('Minutes to suppress repeated alerts');
            $table->integer('max_alerts_per_hour')->default(5);
            $table->integer('max_alerts_per_day')->default(20);
            $table->timestamp('suppression_until')->nullable();
            $table->integer('suppressed_alert_count')->default(0);

            // Business Hours and Time Restrictions
            $table->boolean('respect_business_hours')->default(false);
            $table->json('business_hours_schedule')->nullable()->comment('Business hours configuration');
            $table->string('time_zone', 50)->default('UTC');
            $table->boolean('weekend_notifications')->default(true);
            $table->boolean('holiday_notifications')->default(false);

            // Alert Actions and Automation
            $table->boolean('enable_automated_actions')->default(false);
            $table->json('automated_actions')->nullable()->comment('Actions to take when triggered');
            $table->boolean('auto_suspend_services')->default(false);
            $table->boolean('auto_limit_usage')->default(false);
            $table->boolean('auto_purchase_additional_usage')->default(false);
            $table->json('automation_parameters')->nullable();

            // Performance and Efficiency
            $table->boolean('enable_batching')->default(true)->comment('Batch alerts for efficiency');
            $table->integer('batch_size')->default(50);
            $table->integer('batch_interval_minutes')->default(15);
            $table->timestamp('last_batch_processed_at')->nullable();
            $table->integer('processing_priority')->default(100);

            // Alert History and Analytics
            $table->json('recent_alerts')->nullable()->comment('Recent alert history');
            $table->decimal('alert_accuracy_percentage', 5, 2)->default(0)->comment('Accuracy of predictive alerts');
            $table->integer('false_positive_count')->default(0);
            $table->integer('false_negative_count')->default(0);
            $table->json('performance_metrics')->nullable();

            // Integration with External Systems
            $table->string('external_alert_id', 100)->nullable()->index();
            $table->json('integration_settings')->nullable();
            $table->boolean('sync_with_monitoring_systems')->default(false);
            $table->timestamp('last_external_sync_at')->nullable();

            // Alert Testing and Validation
            $table->boolean('is_test_alert')->default(false);
            $table->timestamp('last_test_triggered_at')->nullable();
            $table->json('test_results')->nullable()->comment('Alert testing results');
            $table->boolean('test_mode_enabled')->default(false);

            // Compliance and Audit
            $table->boolean('requires_acknowledgment')->default(false);
            $table->json('acknowledgment_log')->nullable()->comment('Who acknowledged alerts and when');
            $table->boolean('audit_trail_enabled')->default(true);
            $table->json('compliance_requirements')->nullable();

            // Alert Effectiveness and Optimization
            $table->decimal('cost_savings_generated', 12, 2)->default(0)->comment('Cost savings from alert actions');
            $table->decimal('revenue_protected', 12, 2)->default(0)->comment('Revenue protected by alerts');
            $table->integer('issues_prevented')->default(0)->comment('Number of issues prevented');
            $table->json('optimization_suggestions')->nullable();

            // Alert Lifecycle Management
            $table->string('alert_lifecycle_stage', 20)->default('active')->comment('active, testing, deprecated, archived');
            $table->timestamp('alert_created_date')->useCurrent();
            $table->timestamp('alert_last_modified_date')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->text('modification_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign Key Constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('usage_pool_id')->references('id')->on('usage_pools')->onDelete('set null');
            $table->foreign('usage_bucket_id')->references('id')->on('usage_buckets')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Performance Indexes
            $table->index(['company_id', 'alert_type', 'is_active'], 'usage_alerts_type_active_idx');
            $table->index(['client_id', 'alert_status', 'is_active'], 'usage_alerts_client_status_idx');
            $table->index(['usage_type', 'threshold_type', 'is_active'], 'usage_alerts_usage_threshold_idx');
            $table->index(['last_check_at', 'is_active'], 'usage_alerts_check_time_idx');
            $table->index(['next_check_at', 'is_active'], 'usage_alerts_next_check_idx');
            $table->index(['alert_status', 'severity_level'], 'usage_alerts_status_severity_idx');
            $table->index(['suppression_until', 'is_active'], 'usage_alerts_suppression_idx');
            $table->index(['enable_predictive_alerts', 'prediction_horizon_hours'], 'usage_alerts_predictive_idx');

            // Add computed column for next check time (virtual column)
            DB::statement("ALTER TABLE usage_alerts ADD COLUMN next_check_at TIMESTAMP GENERATED ALWAYS AS (
                CASE 
                    WHEN measurement_period = 'real_time' THEN DATE_ADD(last_check_at, INTERVAL 5 MINUTE)
                    WHEN measurement_period = 'daily' THEN DATE_ADD(last_check_at, INTERVAL 1 HOUR)
                    WHEN measurement_period = 'weekly' THEN DATE_ADD(last_check_at, INTERVAL 4 HOUR)
                    WHEN measurement_period = 'monthly' THEN DATE_ADD(last_check_at, INTERVAL 12 HOUR)
                    ELSE DATE_ADD(last_check_at, INTERVAL 1 HOUR)
                END
            ) VIRTUAL");

            // Unique Constraints
            $table->unique(['company_id', 'alert_code'], 'usage_alerts_code_unique');
        });

        // Add table comment
        DB::statement("ALTER TABLE usage_alerts COMMENT = 'Usage threshold monitoring and alerting system with predictive analytics and automated notifications'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_alerts');
    }
}