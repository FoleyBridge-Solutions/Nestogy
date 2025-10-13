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
            $table->json('portal_branding_settings')->nullable()->after('client_portal_enable');
            $table->json('portal_customization_settings')->nullable()->after('portal_branding_settings');
            $table->json('portal_access_controls')->nullable()->after('portal_customization_settings');
            $table->json('portal_feature_toggles')->nullable()->after('portal_access_controls');
            $table->boolean('portal_self_service_tickets')->default(true)->after('portal_feature_toggles');
            $table->boolean('portal_knowledge_base_access')->default(true)->after('portal_self_service_tickets');
            $table->boolean('portal_invoice_access')->default(true)->after('portal_knowledge_base_access');
            $table->boolean('portal_payment_processing')->default(false)->after('portal_invoice_access');
            $table->boolean('portal_asset_visibility')->default(false)->after('portal_payment_processing');
            $table->json('portal_sso_settings')->nullable()->after('portal_asset_visibility');
            $table->json('portal_mobile_settings')->nullable()->after('portal_sso_settings');
            $table->json('portal_dashboard_settings')->nullable()->after('portal_mobile_settings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'portal_dashboard_settings',
                'portal_mobile_settings',
                'portal_sso_settings',
                'portal_asset_visibility',
                'portal_payment_processing',
                'portal_invoice_access',
                'portal_knowledge_base_access',
                'portal_self_service_tickets',
                'portal_feature_toggles',
                'portal_access_controls',
                'portal_customization_settings',
                'portal_branding_settings',
            ]);
        });
    }
};
