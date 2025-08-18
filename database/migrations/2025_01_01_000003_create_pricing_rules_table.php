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
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name')->nullable();
            
            // Pricing Model
            $table->enum('pricing_model', ['fixed', 'tiered', 'volume', 'usage', 'package', 'custom'])->default('fixed');
            $table->enum('discount_type', ['percentage', 'fixed', 'override'])->nullable();
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->decimal('price_override', 10, 2)->nullable();
            
            // Quantity Rules
            $table->integer('min_quantity')->nullable();
            $table->integer('max_quantity')->nullable();
            $table->integer('quantity_increment')->default(1);
            
            // Time-based Rules
            $table->dateTime('valid_from')->nullable();
            $table->dateTime('valid_until')->nullable();
            $table->json('applicable_days')->nullable(); // Days of week
            $table->json('applicable_hours')->nullable(); // Hours of day
            $table->boolean('is_promotional')->default(false);
            $table->string('promo_code')->nullable();
            
            // Conditions
            $table->json('conditions')->nullable(); // Complex rule conditions
            $table->integer('priority')->default(0); // Higher priority rules apply first
            $table->boolean('is_active')->default(true);
            $table->boolean('is_combinable')->default(false); // Can combine with other rules
            
            // Usage Tracking
            $table->integer('max_uses')->nullable();
            $table->integer('uses_count')->default(0);
            $table->integer('max_uses_per_client')->nullable();
            
            // Approval
            $table->boolean('requires_approval')->default(false);
            $table->decimal('approval_threshold', 10, 2)->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'product_id', 'is_active']);
            $table->index(['company_id', 'client_id']);
            $table->index(['valid_from', 'valid_until']);
            $table->index('promo_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};