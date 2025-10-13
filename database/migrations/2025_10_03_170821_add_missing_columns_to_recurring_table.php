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
        Schema::table('recurring', function (Blueprint $table) {
            // Add JSON columns for service tiers and usage
            $table->json('service_tiers')->nullable()->after('amount');
            $table->json('usage_allowances')->nullable()->after('service_tiers');
            $table->json('overage_rates')->nullable()->after('usage_allowances');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recurring', function (Blueprint $table) {
            $table->dropColumn(['service_tiers', 'usage_allowances', 'overage_rates']);
        });
    }
};
