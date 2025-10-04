<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('credit_applications') && !Schema::hasColumn('credit_applications', 'applied_by')) {
            Schema::table('credit_applications', function (Blueprint $table) {
                $table->unsignedBigInteger('applied_by')->nullable()->after('company_id');
                $table->foreign('applied_by')->references('id')->on('users')->onDelete('set null');
            });
        }

        if (Schema::hasTable('credit_notes') && !Schema::hasColumn('credit_notes', 'created_by')) {
            Schema::table('credit_notes', function (Blueprint $table) {
                $table->unsignedBigInteger('created_by')->nullable()->after('company_id');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            });
        }

        if (Schema::hasTable('dashboard_widgets') && !Schema::hasColumn('dashboard_widgets', 'widget_name')) {
            Schema::table('dashboard_widgets', function (Blueprint $table) {
                $table->string('widget_name')->after('widget_type');
            });
        }

        if (Schema::hasTable('expense_categories') && !Schema::hasColumn('expense_categories', 'sort_order')) {
            Schema::table('expense_categories', function (Blueprint $table) {
                $table->integer('sort_order')->default(0)->after('code');
            });
        }

        if (Schema::hasTable('quote_invoice_conversions') && !Schema::hasColumn('quote_invoice_conversions', 'current_step')) {
            Schema::table('quote_invoice_conversions', function (Blueprint $table) {
                $table->integer('current_step')->default(1)->after('activation_status');
            });
        }

        if (Schema::hasTable('recurring') && !Schema::hasColumn('recurring', 'email_invoice')) {
            Schema::table('recurring', function (Blueprint $table) {
                $table->boolean('email_invoice')->default(true)->after('invoice_terms_days');
            });
        }

        if (Schema::hasTable('refund_requests') && !Schema::hasColumn('refund_requests', 'requested_at')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->timestamp('requested_at')->nullable()->after('created_at');
            });
        }

        if (Schema::hasTable('refund_transactions') && !Schema::hasColumn('refund_transactions', 'max_retries')) {
            Schema::table('refund_transactions', function (Blueprint $table) {
                $table->integer('max_retries')->default(3)->after('initiated_at');
            });
        }

        // Removed: sla_escalation_policies already exists from 2025_08_12_100001_add_comprehensive_settings_fields.php

        if (Schema::hasTable('subsidiary_permissions') && !Schema::hasColumn('subsidiary_permissions', 'name')) {
            Schema::table('subsidiary_permissions', function (Blueprint $table) {
                $table->string('name')->after('company_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('credit_applications', 'applied_by')) {
            Schema::table('credit_applications', function (Blueprint $table) {
                $table->dropForeign(['applied_by']);
                $table->dropColumn('applied_by');
            });
        }

        if (Schema::hasColumn('credit_notes', 'created_by')) {
            Schema::table('credit_notes', function (Blueprint $table) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            });
        }

        if (Schema::hasColumn('dashboard_widgets', 'widget_name')) {
            Schema::table('dashboard_widgets', function (Blueprint $table) {
                $table->dropColumn('widget_name');
            });
        }

        if (Schema::hasColumn('expense_categories', 'sort_order')) {
            Schema::table('expense_categories', function (Blueprint $table) {
                $table->dropColumn('sort_order');
            });
        }

        if (Schema::hasColumn('quote_invoice_conversions', 'current_step')) {
            Schema::table('quote_invoice_conversions', function (Blueprint $table) {
                $table->dropColumn('current_step');
            });
        }

        if (Schema::hasColumn('recurring', 'email_invoice')) {
            Schema::table('recurring', function (Blueprint $table) {
                $table->dropColumn('email_invoice');
            });
        }

        if (Schema::hasColumn('refund_requests', 'requested_at')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->dropColumn('requested_at');
            });
        }

        if (Schema::hasColumn('refund_transactions', 'max_retries')) {
            Schema::table('refund_transactions', function (Blueprint $table) {
                $table->dropColumn('max_retries');
            });
        }

        // Removed: sla_escalation_policies drop (never added by this migration)

        if (Schema::hasColumn('subsidiary_permissions', 'name')) {
            Schema::table('subsidiary_permissions', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }
    }
};
