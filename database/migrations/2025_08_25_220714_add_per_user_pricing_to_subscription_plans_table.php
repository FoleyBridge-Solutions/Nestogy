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
        Schema::table('subscription_plans', function (Blueprint $table) {
            // Add per-user pricing fields
            $table->decimal('price_per_user_monthly', 10, 2)->nullable()->after('price_monthly');
            $table->enum('pricing_model', ['fixed', 'per_user', 'hybrid'])->default('per_user')->after('price_per_user_monthly');
            $table->integer('minimum_users')->default(1)->after('pricing_model');
            $table->decimal('base_price', 10, 2)->default(0)->after('minimum_users'); // For hybrid model

            // Add index for efficient querying
            $table->index(['pricing_model', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropIndex(['pricing_model', 'is_active']);
            $table->dropColumn([
                'price_per_user_monthly',
                'pricing_model',
                'minimum_users',
                'base_price',
            ]);
        });
    }
};
