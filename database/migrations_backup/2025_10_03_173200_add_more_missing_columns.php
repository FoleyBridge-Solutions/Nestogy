<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('credit_applications') && !Schema::hasColumn('credit_applications', 'application_number')) {
            Schema::table('credit_applications', function (Blueprint $table) {
                $table->string('application_number')->unique()->after('id');
            });
        }

        if (Schema::hasTable('credit_notes') && !Schema::hasColumn('credit_notes', 'number')) {
            Schema::table('credit_notes', function (Blueprint $table) {
                $table->string('number')->after('id');
            });
        }

        if (Schema::hasTable('dashboard_widgets') && !Schema::hasColumn('dashboard_widgets', 'widget_type')) {
            Schema::table('dashboard_widgets', function (Blueprint $table) {
                $table->string('widget_type')->default('chart')->after('company_id');
            });
        }

        if (Schema::hasTable('expense_categories') && !Schema::hasColumn('expense_categories', 'approval_threshold')) {
            Schema::table('expense_categories', function (Blueprint $table) {
                $table->decimal('approval_threshold', 15, 2)->nullable()->after('requires_approval');
            });
        }

        if (Schema::hasTable('in_app_notifications') && !Schema::hasColumn('in_app_notifications', 'name')) {
            Schema::table('in_app_notifications', function (Blueprint $table) {
                $table->string('name')->after('company_id');
            });
        }

        if (Schema::hasTable('notification_preferences') && !Schema::hasColumn('notification_preferences', 'name')) {
            Schema::table('notification_preferences', function (Blueprint $table) {
                $table->string('name')->after('company_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('credit_applications', 'application_number')) {
            Schema::table('credit_applications', function (Blueprint $table) {
                $table->dropColumn('application_number');
            });
        }

        if (Schema::hasColumn('credit_notes', 'number')) {
            Schema::table('credit_notes', function (Blueprint $table) {
                $table->dropColumn('number');
            });
        }

        if (Schema::hasColumn('dashboard_widgets', 'widget_type')) {
            Schema::table('dashboard_widgets', function (Blueprint $table) {
                $table->dropColumn('widget_type');
            });
        }

        if (Schema::hasColumn('expense_categories', 'approval_threshold')) {
            Schema::table('expense_categories', function (Blueprint $table) {
                $table->dropColumn('approval_threshold');
            });
        }

        if (Schema::hasColumn('in_app_notifications', 'name')) {
            Schema::table('in_app_notifications', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }

        if (Schema::hasColumn('notification_preferences', 'name')) {
            Schema::table('notification_preferences', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }
    }
};
