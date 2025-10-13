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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->string('country')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('logo')->nullable();
            $table->string('locale')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->timestamps();
            $table->json('hourly_rate_config')->nullable();
            $table->decimal('default_standard_rate', 10, 2)->default(150.00);
            $table->decimal('default_after_hours_rate', 10, 2)->default(225.00);
            $table->decimal('default_emergency_rate', 10, 2)->default(300.00);
            $table->decimal('default_weekend_rate', 10, 2)->default(200.00);
            $table->decimal('default_holiday_rate', 10, 2)->default(250.00);
            $table->decimal('after_hours_multiplier', 5, 2)->default(1.5);
            $table->decimal('emergency_multiplier', 5, 2)->default(2.0);
            $table->decimal('weekend_multiplier', 5, 2)->default(1.5);
            $table->decimal('holiday_multiplier', 5, 2)->default(2.0);
            $table->enum('rate_calculation_method', ['fixed_rates', 'multipliers'])->default('fixed_rates');
            $table->decimal('minimum_billing_increment', 5, 2)->default(0.25);
            $table->enum('time_rounding_method', ['none', 'up', 'down', 'nearest'])->default('nearest');
            $table->unsignedBigInteger('parent_company_id')->nullable();
            $table->enum('company_type', ['root', 'subsidiary', 'division'])->default('root');
            $table->unsignedInteger('organizational_level')->default(0);
            $table->json('subsidiary_settings')->nullable();
            $table->enum('access_level', ['full', 'limited', 'read_only'])->default('full');
            $table->enum('billing_type', ['independent', 'parent_billed', 'shared'])->default('independent');
            $table->unsignedBigInteger('billing_parent_id')->nullable();
            $table->boolean('can_create_subsidiaries')->default(false);
            $table->unsignedInteger('max_subsidiary_depth')->default(3);
            $table->json('inherited_permissions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('suspended_at')->nullable();
            $table->unsignedBigInteger('client_record_id')->nullable();
            $table->string('suspension_reason')->nullable();
            $table->enum('email_provider_type', ['manual', 'microsoft365', 'google_workspace', 'exchange', 'custom_oauth'])
                ->default('manual')
                ;
            $table->json('email_provider_config')->nullable();
            $table->string('size')->nullable()->comment('solo, small, medium, large, enterprise');
            $table->integer('employee_count')->nullable();
            $table->json('branding')->nullable();
            $table->json('company_info')->nullable();
            $table->json('social_links')->nullable();

            // Indexes
            $table->index('name');
            $table->index('email');
            $table->index('currency');

            $table->index(['parent_company_id', 'company_type'], 'companies_hierarchy_idx');
            $table->index(['organizational_level'], 'companies_level_idx');
            $table->index(['billing_parent_id'], 'companies_billing_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
