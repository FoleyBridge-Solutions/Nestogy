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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('stripe_price_id')->unique();
            $table->decimal('price_monthly', 10, 2);
            $table->integer('user_limit')->nullable(); // NULL = unlimited
            $table->json('features')->nullable(); // Feature flags for the plan
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->decimal('price_per_user_monthly', 10, 2)->nullable();
            $table->enum('pricing_model', ['fixed', 'per_user', 'hybrid'])->default('per_user');
            $table->integer('minimum_users')->default(1);
            $table->decimal('base_price', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->nullable();
            $table->string('stripe_price_id_yearly')->nullable();
            $table->integer('max_users')->nullable();
            $table->integer('max_clients')->nullable();

            $table->index(['pricing_model', 'is_active']);
            $table->string('slug', 50)->unique();
            $table->index('is_active');
            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
