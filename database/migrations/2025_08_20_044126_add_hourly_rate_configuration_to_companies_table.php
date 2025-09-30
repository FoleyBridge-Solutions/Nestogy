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
        Schema::table('companies', function (Blueprint $table) {
            // Hourly rate configuration JSON field
            $table->json('hourly_rate_config')->nullable()->after('currency');

            // Default hourly rates for different time periods
            $table->decimal('default_standard_rate', 10, 2)->default(150.00)->after('hourly_rate_config');
            $table->decimal('default_after_hours_rate', 10, 2)->default(225.00)->after('default_standard_rate');
            $table->decimal('default_emergency_rate', 10, 2)->default(300.00)->after('default_after_hours_rate');
            $table->decimal('default_weekend_rate', 10, 2)->default(200.00)->after('default_emergency_rate');
            $table->decimal('default_holiday_rate', 10, 2)->default(250.00)->after('default_weekend_rate');

            // Rate multipliers (alternative to fixed rates)
            $table->decimal('after_hours_multiplier', 5, 2)->default(1.5)->after('default_holiday_rate');
            $table->decimal('emergency_multiplier', 5, 2)->default(2.0)->after('after_hours_multiplier');
            $table->decimal('weekend_multiplier', 5, 2)->default(1.5)->after('emergency_multiplier');
            $table->decimal('holiday_multiplier', 5, 2)->default(2.0)->after('weekend_multiplier');

            // Billing configuration
            $table->enum('rate_calculation_method', ['fixed_rates', 'multipliers'])->default('fixed_rates')->after('holiday_multiplier');
            $table->decimal('minimum_billing_increment', 5, 2)->default(0.25)->after('rate_calculation_method'); // 15 minutes
            $table->enum('time_rounding_method', ['none', 'up', 'down', 'nearest'])->default('nearest')->after('minimum_billing_increment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'hourly_rate_config',
                'default_standard_rate',
                'default_after_hours_rate',
                'default_emergency_rate',
                'default_weekend_rate',
                'default_holiday_rate',
                'after_hours_multiplier',
                'emergency_multiplier',
                'weekend_multiplier',
                'holiday_multiplier',
                'rate_calculation_method',
                'minimum_billing_increment',
                'time_rounding_method',
            ]);
        });
    }
};
