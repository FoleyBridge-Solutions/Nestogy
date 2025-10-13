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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->boolean('lead')->default(false);
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('type')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('country')->default('US');
            $table->string('website')->nullable();
            $table->string('referral')->nullable();
            $table->decimal('rate', 15, 2)->nullable();
            $table->string('currency_code', 3)->default('USD');
            $table->integer('net_terms')->default(30);
            $table->string('tax_id_number')->nullable();
            $table->integer('rmm_id')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->string('billing_contact')->nullable();
            $table->string('technical_contact')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamp('contract_start_date')->nullable();
            $table->timestamp('contract_end_date')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('sla_id')->nullable();
            $table->decimal('custom_standard_rate', 10, 2)->nullable();
            $table->decimal('custom_after_hours_rate', 10, 2)->nullable();
            $table->decimal('custom_emergency_rate', 10, 2)->nullable();
            $table->decimal('custom_weekend_rate', 10, 2)->nullable();
            $table->decimal('custom_holiday_rate', 10, 2)->nullable();
            $table->decimal('custom_after_hours_multiplier', 5, 2)->nullable();
            $table->decimal('custom_emergency_multiplier', 5, 2)->nullable();
            $table->decimal('custom_weekend_multiplier', 5, 2)->nullable();
            $table->decimal('custom_holiday_multiplier', 5, 2)->nullable();
            $table->enum('custom_rate_calculation_method', ['fixed_rates', 'multipliers'])->nullable();
            $table->decimal('custom_minimum_billing_increment', 5, 2)->nullable();
            $table->enum('custom_time_rounding_method', ['none', 'up', 'down', 'nearest'])->nullable();
            $table->boolean('use_custom_rates')->default(false);
            $table->unsignedBigInteger('company_link_id')->nullable();
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_subscription_id')->nullable();
            $table->string('subscription_status')->default('trialing');
            $table->unsignedBigInteger('subscription_plan_id')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('next_billing_date')->nullable();
            $table->timestamp('subscription_started_at')->nullable();
            $table->timestamp('subscription_canceled_at')->nullable();
            $table->integer('current_user_count')->default(0);
            $table->string('industry')->nullable();
            $table->integer('employee_count')->nullable();
            $table->softDeletes();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('accessed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->index(['company_id', 'sla_id']);

            $table->index(['status', 'created_at']);
            $table->index('company_name');
            $table->index('company_id');
            $table->index(['company_id', 'status']);
            $table->index('lead');
            $table->index('type');
            $table->index('accessed_at');
            $table->index(['company_id', 'lead']);
            $table->index(['company_id', 'archived_at']);

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
