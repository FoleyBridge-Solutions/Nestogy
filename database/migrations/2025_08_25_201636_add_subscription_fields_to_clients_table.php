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
            // SaaS subscription fields
            $table->unsignedBigInteger('company_link_id')->nullable()->after('use_custom_rates');
            $table->string('stripe_customer_id')->nullable()->after('company_link_id');
            $table->string('stripe_subscription_id')->nullable()->after('stripe_customer_id');
            $table->string('subscription_status')->default('trialing')->after('stripe_subscription_id');
            $table->unsignedBigInteger('subscription_plan_id')->nullable()->after('subscription_status');
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_plan_id');
            $table->timestamp('next_billing_date')->nullable()->after('trial_ends_at');
            $table->timestamp('subscription_started_at')->nullable()->after('next_billing_date');
            $table->timestamp('subscription_canceled_at')->nullable()->after('subscription_started_at');
            $table->integer('current_user_count')->default(0)->after('subscription_canceled_at');

            // Add foreign keys
            $table->foreign('company_link_id')->references('id')->on('companies')->onDelete('set null');
            if (Schema::hasTable('subscription_plans')) {
                $table->foreign('subscription_plan_id')->references('id')->on('subscription_plans')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['company_link_id']);
            if (Schema::hasTable('subscription_plans')) {
                $table->dropForeign(['subscription_plan_id']);
            }

            // Drop columns
            $table->dropColumn([
                'company_link_id',
                'stripe_customer_id',
                'stripe_subscription_id',
                'subscription_status',
                'subscription_plan_id',
                'trial_ends_at',
                'next_billing_date',
                'subscription_started_at',
                'subscription_canceled_at',
                'current_user_count',
            ]);
        });
    }
};
