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
        // Create pricing_rules table first
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('rule_name');
            $table->string('rule_type'); // 'tiered', 'volume', 'flat', 'usage_based'
            $table->json('conditions')->nullable();
            $table->json('pricing_structure');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index(['company_id', 'rule_type', 'is_active']);
        });

        // Create usage_pools table
        Schema::create('usage_pools', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('client_id')->index();
            $table->string('pool_name');
            $table->string('pool_type'); // 'minutes', 'data', 'messages', etc.
            $table->decimal('total_allocation', 15, 2);
            $table->decimal('used_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2);
            $table->date('pool_start_date');
            $table->date('pool_end_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->index(['company_id', 'client_id', 'pool_type']);
        });

        // Create usage_buckets table
        Schema::create('usage_buckets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('client_id')->index();
            $table->unsignedBigInteger('usage_pool_id')->nullable()->index();
            $table->string('bucket_code', 50)->unique();
            $table->string('bucket_name');
            $table->string('bucket_type'); // 'prepaid', 'postpaid', 'unlimited', 'quota'
            $table->decimal('bucket_capacity', 15, 2)->nullable();
            $table->decimal('current_usage', 15, 2)->default(0);
            $table->decimal('remaining_capacity', 15, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('usage_pool_id')->references('id')->on('usage_pools')->onDelete('set null');
            $table->unique(['company_id', 'client_id', 'bucket_code']);
        });

        // Create usage_tiers table  
        Schema::create('usage_tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pricing_rule_id')->index();
            $table->integer('tier_level');
            $table->decimal('tier_min', 15, 2);
            $table->decimal('tier_max', 15, 2)->nullable();
            $table->decimal('rate', 10, 4);
            $table->timestamps();
            
            $table->foreign('pricing_rule_id')->references('id')->on('pricing_rules')->onDelete('cascade');
            $table->index(['pricing_rule_id', 'tier_level']);
        });

        // Create usage_records table
        Schema::create('usage_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('client_id')->index();
            $table->unsignedBigInteger('invoice_id')->nullable()->index();
            $table->unsignedBigInteger('usage_pool_id')->nullable()->index();
            $table->unsignedBigInteger('usage_bucket_id')->nullable()->index();
            $table->string('usage_type'); // 'voice', 'data', 'sms', etc.
            $table->string('service_type'); // 'local', 'long_distance', 'international'
            $table->decimal('usage_amount', 15, 6);
            $table->string('usage_unit'); // 'minutes', 'MB', 'messages'
            $table->decimal('unit_rate', 10, 4)->nullable();
            $table->decimal('total_cost', 10, 2)->default(0);
            $table->date('usage_date');
            $table->timestamp('usage_timestamp');
            $table->string('source_identifier')->nullable(); // phone number, device ID, etc.
            $table->string('destination_identifier')->nullable();
            $table->json('usage_metadata')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
            $table->foreign('usage_pool_id')->references('id')->on('usage_pools')->onDelete('set null');
            $table->foreign('usage_bucket_id')->references('id')->on('usage_buckets')->onDelete('set null');
            
            $table->index(['company_id', 'client_id', 'usage_date'], 'usage_client_date_idx');
            $table->index(['company_id', 'usage_type', 'service_type', 'usage_date'], 'usage_type_service_date_idx');
        });

        // Create usage_alerts table
        Schema::create('usage_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('client_id')->index();
            $table->unsignedBigInteger('usage_pool_id')->nullable()->index();
            $table->unsignedBigInteger('usage_bucket_id')->nullable()->index();
            $table->string('alert_type'); // 'threshold', 'overage', 'expiration'
            $table->decimal('threshold_percentage', 5, 2);
            $table->decimal('current_percentage', 5, 2);
            $table->boolean('is_triggered')->default(false);
            $table->timestamp('triggered_at')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('usage_pool_id')->references('id')->on('usage_pools')->onDelete('cascade');
            $table->foreign('usage_bucket_id')->references('id')->on('usage_buckets')->onDelete('cascade');
            
            $table->index(['company_id', 'client_id', 'alert_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_alerts');
        Schema::dropIfExists('usage_records');
        Schema::dropIfExists('usage_tiers');
        Schema::dropIfExists('usage_buckets');
        Schema::dropIfExists('usage_pools');
        Schema::dropIfExists('pricing_rules');
    }
};