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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id');
            
            // Type and provider
            $table->string('type', 50); // credit_card, bank_account, digital_wallet, crypto
            $table->string('provider', 50); // stripe, square, paypal, plaid, etc.
            $table->string('provider_payment_method_id')->nullable();
            $table->string('provider_customer_id')->nullable();
            $table->string('token')->nullable();
            $table->string('fingerprint')->nullable()->index();
            
            // General info
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            
            // Credit card details
            $table->string('card_brand', 20)->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->string('card_exp_month', 2)->nullable();
            $table->string('card_exp_year', 4)->nullable();
            $table->string('card_holder_name')->nullable();
            $table->string('card_country', 2)->nullable();
            $table->string('card_funding', 20)->nullable();
            $table->boolean('card_checks_cvc_check')->nullable();
            $table->boolean('card_checks_address_line1_check')->nullable();
            $table->boolean('card_checks_address_postal_code_check')->nullable();
            
            // Bank account details  
            $table->string('bank_name')->nullable();
            $table->string('bank_account_type', 20)->nullable();
            $table->string('bank_account_last_four', 4)->nullable();
            $table->string('bank_routing_number_last_four', 4)->nullable();
            $table->string('bank_account_holder_type', 20)->nullable();
            $table->string('bank_account_holder_name')->nullable();
            $table->string('bank_country', 2)->nullable();
            $table->string('bank_currency', 3)->nullable();
            
            // Digital wallet details
            $table->string('wallet_type', 50)->nullable();
            $table->string('wallet_email')->nullable();
            $table->string('wallet_phone')->nullable();
            
            // Cryptocurrency details
            $table->string('crypto_type', 20)->nullable();
            $table->string('crypto_address')->nullable();
            $table->string('crypto_network')->nullable();
            
            // Billing address
            $table->string('billing_name')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('billing_phone')->nullable();
            $table->string('billing_address_line1')->nullable();
            $table->string('billing_address_line2')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_state')->nullable();
            $table->string('billing_postal_code')->nullable();
            $table->string('billing_country', 2)->nullable();
            
            // Security and compliance
            $table->json('security_checks')->nullable();
            $table->json('compliance_data')->nullable();
            $table->boolean('requires_3d_secure')->default(false);
            $table->json('risk_assessment')->nullable();
            
            // Usage tracking
            $table->integer('successful_payments_count')->default(0);
            $table->integer('failed_payments_count')->default(0);
            $table->decimal('total_payment_amount', 15, 2)->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('last_failed_at')->nullable();
            $table->string('last_failure_reason')->nullable();
            
            // Additional data
            $table->json('metadata')->nullable();
            $table->json('preferences')->nullable();
            $table->json('restrictions')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'client_id']);
            $table->index(['company_id', 'is_active', 'is_default']);
            $table->index('provider_payment_method_id');
            $table->index('provider_customer_id');
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
