<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'subsidiary_permissions',
            'services',
            'notification_preferences',
            'in_app_notifications',
            'dashboard_widgets',
            'communication_logs',
            'bouncer_roles',
            'bouncer_abilities',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'company_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id');
                    $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'subsidiary_permissions',
            'services',
            'notification_preferences',
            'in_app_notifications',
            'dashboard_widgets',
            'communication_logs',
            'bouncer_roles',
            'bouncer_abilities',
        ];

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'company_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropForeign(['company_id']);
                    $table->dropColumn('company_id');
                });
            }
        }
    }
};
