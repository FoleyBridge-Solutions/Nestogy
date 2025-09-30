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
        Schema::table('settings', function (Blueprint $table) {
            // Authentication Settings
            if (! Schema::hasColumn('settings', 'remember_me_enabled')) {
                $table->boolean('remember_me_enabled')->default(true)->after('two_factor_enabled');
            }
            if (! Schema::hasColumn('settings', 'email_verification_required')) {
                $table->boolean('email_verification_required')->default(false)->after('remember_me_enabled');
            }
            if (! Schema::hasColumn('settings', 'api_authentication_enabled')) {
                $table->boolean('api_authentication_enabled')->default(false)->after('email_verification_required');
            }

            // Login Settings - max_login_attempts already exists, skip it
            if (! Schema::hasColumn('settings', 'login_lockout_duration')) {
                $table->integer('login_lockout_duration')->default(15)->after('max_login_attempts'); // in minutes
            }

            // Password Policy
            if (! Schema::hasColumn('settings', 'password_min_length')) {
                $table->integer('password_min_length')->default(8)->after('login_lockout_duration');
            }
            if (! Schema::hasColumn('settings', 'password_expiry_days')) {
                $table->integer('password_expiry_days')->default(0)->after('password_min_length'); // 0 = no expiry
            }
            if (! Schema::hasColumn('settings', 'password_require_uppercase')) {
                $table->boolean('password_require_uppercase')->default(true)->after('password_expiry_days');
            }
            if (! Schema::hasColumn('settings', 'password_require_lowercase')) {
                $table->boolean('password_require_lowercase')->default(true)->after('password_require_uppercase');
            }
            if (! Schema::hasColumn('settings', 'password_require_number')) {
                $table->boolean('password_require_number')->default(true)->after('password_require_lowercase');
            }
            if (! Schema::hasColumn('settings', 'password_require_special')) {
                $table->boolean('password_require_special')->default(false)->after('password_require_number');
            }
            if (! Schema::hasColumn('settings', 'password_history_count')) {
                $table->integer('password_history_count')->default(5)->after('password_require_special');
            }

            // Session Management
            if (! Schema::hasColumn('settings', 'session_lifetime')) {
                $table->integer('session_lifetime')->default(120)->after('password_history_count'); // in minutes
            }
            if (! Schema::hasColumn('settings', 'idle_timeout')) {
                $table->integer('idle_timeout')->default(15)->after('session_lifetime'); // in minutes
            }
            if (! Schema::hasColumn('settings', 'single_session_per_user')) {
                $table->boolean('single_session_per_user')->default(false)->after('idle_timeout');
            }
            if (! Schema::hasColumn('settings', 'logout_on_browser_close')) {
                $table->boolean('logout_on_browser_close')->default(false)->after('single_session_per_user');
            }

            // IP Restrictions
            if (! Schema::hasColumn('settings', 'ip_whitelist_enabled')) {
                $table->boolean('ip_whitelist_enabled')->default(false)->after('logout_on_browser_close');
            }
            if (! Schema::hasColumn('settings', 'whitelisted_ips')) {
                $table->text('whitelisted_ips')->nullable()->after('ip_whitelist_enabled');
            }

            // OAuth Settings
            if (! Schema::hasColumn('settings', 'oauth_google_enabled')) {
                $table->boolean('oauth_google_enabled')->default(false)->after('whitelisted_ips');
            }
            if (! Schema::hasColumn('settings', 'oauth_google_client_id')) {
                $table->string('oauth_google_client_id')->nullable()->after('oauth_google_enabled');
            }
            if (! Schema::hasColumn('settings', 'oauth_google_client_secret')) {
                $table->text('oauth_google_client_secret')->nullable()->after('oauth_google_client_id');
            }

            if (! Schema::hasColumn('settings', 'oauth_microsoft_enabled')) {
                $table->boolean('oauth_microsoft_enabled')->default(false)->after('oauth_google_client_secret');
            }
            if (! Schema::hasColumn('settings', 'oauth_microsoft_client_id')) {
                $table->string('oauth_microsoft_client_id')->nullable()->after('oauth_microsoft_enabled');
            }
            if (! Schema::hasColumn('settings', 'oauth_microsoft_client_secret')) {
                $table->text('oauth_microsoft_client_secret')->nullable()->after('oauth_microsoft_client_id');
            }

            // SAML SSO
            if (! Schema::hasColumn('settings', 'saml_enabled')) {
                $table->boolean('saml_enabled')->default(false)->after('oauth_microsoft_client_secret');
            }
            if (! Schema::hasColumn('settings', 'saml_entity_id')) {
                $table->string('saml_entity_id')->nullable()->after('saml_enabled');
            }
            if (! Schema::hasColumn('settings', 'saml_sso_url')) {
                $table->string('saml_sso_url')->nullable()->after('saml_entity_id');
            }
            if (! Schema::hasColumn('settings', 'saml_certificate')) {
                $table->text('saml_certificate')->nullable()->after('saml_sso_url');
            }

            // Audit & Logging
            if (! Schema::hasColumn('settings', 'audit_login_attempts')) {
                $table->boolean('audit_login_attempts')->default(true)->after('saml_certificate');
            }
            if (! Schema::hasColumn('settings', 'audit_password_changes')) {
                $table->boolean('audit_password_changes')->default(true)->after('audit_login_attempts');
            }
            if (! Schema::hasColumn('settings', 'audit_permission_changes')) {
                $table->boolean('audit_permission_changes')->default(true)->after('audit_password_changes');
            }
            if (! Schema::hasColumn('settings', 'audit_data_access')) {
                $table->boolean('audit_data_access')->default(false)->after('audit_permission_changes');
            }
            if (! Schema::hasColumn('settings', 'audit_log_retention_days')) {
                $table->integer('audit_log_retention_days')->default(365)->after('audit_data_access');
            }

            // Security Alerts
            if (! Schema::hasColumn('settings', 'alert_suspicious_activity')) {
                $table->boolean('alert_suspicious_activity')->default(false)->after('audit_log_retention_days');
            }
            if (! Schema::hasColumn('settings', 'alert_multiple_failed_logins')) {
                $table->boolean('alert_multiple_failed_logins')->default(true)->after('alert_suspicious_activity');
            }
            if (! Schema::hasColumn('settings', 'security_alert_email')) {
                $table->string('security_alert_email')->nullable()->after('alert_multiple_failed_logins');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $columns = [
                'remember_me_enabled',
                'email_verification_required',
                'api_authentication_enabled',
                'login_lockout_duration',
                'password_min_length',
                'password_expiry_days',
                'password_require_uppercase',
                'password_require_lowercase',
                'password_require_number',
                'password_require_special',
                'password_history_count',
                'session_lifetime',
                'idle_timeout',
                'single_session_per_user',
                'logout_on_browser_close',
                'ip_whitelist_enabled',
                'whitelisted_ips',
                'oauth_google_enabled',
                'oauth_google_client_id',
                'oauth_google_client_secret',
                'oauth_microsoft_enabled',
                'oauth_microsoft_client_id',
                'oauth_microsoft_client_secret',
                'saml_enabled',
                'saml_entity_id',
                'saml_sso_url',
                'saml_certificate',
                'audit_login_attempts',
                'audit_password_changes',
                'audit_permission_changes',
                'audit_data_access',
                'audit_log_retention_days',
                'alert_suspicious_activity',
                'alert_multiple_failed_logins',
                'security_alert_email',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
