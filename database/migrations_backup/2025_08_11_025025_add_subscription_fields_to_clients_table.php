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
            // Link to the tenant company created for this client
            $table->foreignId('company_link_id')->nullable()->constrained('companies')->onDelete('set null');
            
            // Stripe subscription management
            $table->string('stripe_customer_id')->nullable()->unique();
            $table->string('stripe_subscription_id')->nullable()->unique();
            
            // Subscription status and plan
            $table->enum('subscription_status', ['trialing', 'active', 'past_due', 'canceled', 'unpaid'])->default('trialing');
            $table->foreignId('subscription_plan_id')->nullable()->constrained('subscription_plans')->onDelete('set null');
            
            // Trial and billing dates
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('next_billing_date')->nullable();
            $table->timestamp('subscription_started_at')->nullable();
            $table->timestamp('subscription_canceled_at')->nullable();
            
            // Usage tracking
            $table->integer('current_user_count')->default(1);
            
            // Indexes for performance
            $table->index('subscription_status');
            $table->index(['subscription_status', 'trial_ends_at']);
            $table->index(['subscription_status', 'next_billing_date']);
            $table->index('company_link_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['company_link_id']);
            $table->dropForeign(['subscription_plan_id']);
            
            $table->dropIndex(['company_link_id']);
            $table->dropIndex(['subscription_status', 'next_billing_date']);
            $table->dropIndex(['subscription_status', 'trial_ends_at']);
            $table->dropIndex(['subscription_status']);
            
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
