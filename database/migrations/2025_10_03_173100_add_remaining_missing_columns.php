<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('credit_note_items') && !Schema::hasColumn('credit_note_items', 'remaining_credit')) {
            Schema::table('credit_note_items', function (Blueprint $table) {
                $table->decimal('remaining_credit', 15, 2)->default(0)->after('amount');
            });
        }

        if (Schema::hasTable('expense_categories') && !Schema::hasColumn('expense_categories', 'requires_approval')) {
            Schema::table('expense_categories', function (Blueprint $table) {
                $table->boolean('requires_approval')->default(false)->after('is_active');
            });
        }

        if (Schema::hasTable('payment_plans') && !Schema::hasColumn('payment_plans', 'created_by')) {
            Schema::table('payment_plans', function (Blueprint $table) {
                $table->unsignedBigInteger('created_by')->nullable()->after('company_id');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            });
        }

        if (Schema::hasTable('permission_groups') && !Schema::hasColumn('permission_groups', 'company_id')) {
            Schema::table('permission_groups', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('quote_invoice_conversions') && !Schema::hasColumn('quote_invoice_conversions', 'activation_status')) {
            Schema::table('quote_invoice_conversions', function (Blueprint $table) {
                $table->string('activation_status')->default('pending')->after('status');
            });
        }

        if (Schema::hasTable('recurring') && !Schema::hasColumn('recurring', 'invoice_terms_days')) {
            Schema::table('recurring', function (Blueprint $table) {
                $table->integer('invoice_terms_days')->default(30)->after('frequency');
            });
        }

        if (Schema::hasTable('refund_requests') && !Schema::hasColumn('refund_requests', 'requested_by')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('requested_by')->nullable()->after('company_id');
                $table->foreign('requested_by')->references('id')->on('users')->onDelete('set null');
            });
        }

        if (Schema::hasTable('refund_transactions') && !Schema::hasColumn('refund_transactions', 'initiated_at')) {
            Schema::table('refund_transactions', function (Blueprint $table) {
                $table->timestamp('initiated_at')->nullable()->after('created_at');
            });
        }

        if (Schema::hasTable('settings') && !Schema::hasColumn('settings', 'sla_definitions')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->json('sla_definitions')->nullable()->after('value');
            });
        }

        if (Schema::hasTable('usage_alerts') && !Schema::hasColumn('usage_alerts', 'alert_created_date')) {
            Schema::table('usage_alerts', function (Blueprint $table) {
                $table->timestamp('alert_created_date')->nullable()->after('created_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('credit_note_items', 'remaining_credit')) {
            Schema::table('credit_note_items', function (Blueprint $table) {
                $table->dropColumn('remaining_credit');
            });
        }

        if (Schema::hasColumn('expense_categories', 'requires_approval')) {
            Schema::table('expense_categories', function (Blueprint $table) {
                $table->dropColumn('requires_approval');
            });
        }

        if (Schema::hasColumn('payment_plans', 'created_by')) {
            Schema::table('payment_plans', function (Blueprint $table) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            });
        }

        if (Schema::hasColumn('permission_groups', 'company_id')) {
            Schema::table('permission_groups', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }

        if (Schema::hasColumn('quote_invoice_conversions', 'activation_status')) {
            Schema::table('quote_invoice_conversions', function (Blueprint $table) {
                $table->dropColumn('activation_status');
            });
        }

        if (Schema::hasColumn('recurring', 'invoice_terms_days')) {
            Schema::table('recurring', function (Blueprint $table) {
                $table->dropColumn('invoice_terms_days');
            });
        }

        if (Schema::hasColumn('refund_requests', 'requested_by')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->dropForeign(['requested_by']);
                $table->dropColumn('requested_by');
            });
        }

        if (Schema::hasColumn('refund_transactions', 'initiated_at')) {
            Schema::table('refund_transactions', function (Blueprint $table) {
                $table->dropColumn('initiated_at');
            });
        }

        if (Schema::hasColumn('settings', 'sla_definitions')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('sla_definitions');
            });
        }

        if (Schema::hasColumn('usage_alerts', 'alert_created_date')) {
            Schema::table('usage_alerts', function (Blueprint $table) {
                $table->dropColumn('alert_created_date');
            });
        }
    }
};
