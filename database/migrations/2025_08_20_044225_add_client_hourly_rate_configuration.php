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
        Schema::table('clients', function (Blueprint $table) {
            // Client-specific hourly rate overrides
            $table->decimal('custom_standard_rate', 10, 2)->nullable()->after('hourly_rate');
            $table->decimal('custom_after_hours_rate', 10, 2)->nullable()->after('custom_standard_rate');
            $table->decimal('custom_emergency_rate', 10, 2)->nullable()->after('custom_after_hours_rate');
            $table->decimal('custom_weekend_rate', 10, 2)->nullable()->after('custom_emergency_rate');
            $table->decimal('custom_holiday_rate', 10, 2)->nullable()->after('custom_weekend_rate');

            // Client-specific multipliers
            $table->decimal('custom_after_hours_multiplier', 5, 2)->nullable()->after('custom_holiday_rate');
            $table->decimal('custom_emergency_multiplier', 5, 2)->nullable()->after('custom_after_hours_multiplier');
            $table->decimal('custom_weekend_multiplier', 5, 2)->nullable()->after('custom_emergency_multiplier');
            $table->decimal('custom_holiday_multiplier', 5, 2)->nullable()->after('custom_weekend_multiplier');

            // Client-specific billing settings
            $table->enum('custom_rate_calculation_method', ['fixed_rates', 'multipliers'])->nullable()->after('custom_holiday_multiplier');
            $table->decimal('custom_minimum_billing_increment', 5, 2)->nullable()->after('custom_rate_calculation_method');
            $table->enum('custom_time_rounding_method', ['none', 'up', 'down', 'nearest'])->nullable()->after('custom_minimum_billing_increment');

            // Flag to indicate if client uses custom rates or inherits from company
            $table->boolean('use_custom_rates')->default(false)->after('custom_time_rounding_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'custom_standard_rate',
                'custom_after_hours_rate',
                'custom_emergency_rate',
                'custom_weekend_rate',
                'custom_holiday_rate',
                'custom_after_hours_multiplier',
                'custom_emergency_multiplier',
                'custom_weekend_multiplier',
                'custom_holiday_multiplier',
                'custom_rate_calculation_method',
                'custom_minimum_billing_increment',
                'custom_time_rounding_method',
                'use_custom_rates',
            ]);
        });
    }
};
