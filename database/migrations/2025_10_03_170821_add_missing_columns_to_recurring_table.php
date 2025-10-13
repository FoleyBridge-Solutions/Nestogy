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
        if (!Schema::hasTable('recurring')) {
            Schema::create('recurring', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->onDelete('cascade');
                $table->timestamps();
                $table->softDeletes();
            });
        }
        
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
