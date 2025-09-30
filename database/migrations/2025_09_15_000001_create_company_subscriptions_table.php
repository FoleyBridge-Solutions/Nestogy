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
        Schema::create('company_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_plan_id')->nullable()->constrained()->onDelete('set null');

            // Subscription status
            $table->enum('status', ['active', 'trialing', 'past_due', 'canceled', 'suspended', 'expired'])
                ->default('trialing');

            // User limits (NOT including client portal users)
            $table->integer('max_users')->default(2);
            $table->integer('current_user_count')->default(0);

            // Billing information
            $table->decimal('monthly_amount', 10, 2)->default(0);
            $table->string('stripe_subscription_id')->nullable()->unique();
            $table->string('stripe_customer_id')->nullable();

            // Important dates
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('grace_period_ends_at')->nullable();

            // Additional metadata
            $table->json('features')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index(['company_id', 'status']);
            $table->index('stripe_subscription_id');
            $table->index('trial_ends_at');
            $table->index('current_period_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_subscriptions');
    }
};
