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
            $table->string('type'); // credit_card, debit_card, bank_account, paypal, apple_pay, google_pay, cryptocurrency
            $table->string('provider'); // stripe, paypal, authorize_net, square, internal
            $table->string('provider_payment_method_id')->nullable(); // Gateway-specific ID
            $table->string('provider_customer_id')->nullable(); // Gateway customer ID
            $table->string('token', 128)->nullable(); // Tokenized payment method
            $table->string('fingerprint', 64)->nullable(); // Unique fingerprint for duplicate detection
            $table->string('name')->nullable(); // User-friendly name
            $table->string('description')->nullable(); // User description
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            
            // Credit/Debit Card specific fields
            $table->string('card_brand')->nullable(); // visa, mastercard, amex, discover
            $table->string('card_last_four', 4)->nullable();
            $table->string('card_exp_month', 2)->nullable();
            $table->string('card_exp_year', 4)->nullable();
            $table->string('card_holder_name')->nullable();
            $table->string('card_country', 2)->nullable();
            $table->string('card_funding')->nullable(); // credit, debit, prepaid, unknown
            $table->boolean('card_checks_cvc_check')->nullable();
            $table->boolean('card_checks_address_line1_check')->nullable();
            $table->boolean('card_checks_address_postal_code_check')->nullable();
            
            // Bank Account specific fields
            $table->string('bank_name')->nullable();
            $table->string('bank_account_type')->nullable(); // checking, savings
            $table->string('bank_account_last_four', 4)->nullable();
            $table->string('bank_routing_number_last_four', 4)->nullable();
            $table->string('bank_account_holder_type')->nullable(); // individual, company
            $table->string('bank_account_holder_name')->nullable();
            $table->string('bank_country', 2)->nullable();
            $table->string('bank_currency', 3)->nullable();
            
            // Digital Wallet specific fields
            $table->string('wallet_type')->nullable(); // apple_pay, google_pay, paypal
            $table->string('wallet_email')->nullable();
            $table->string('wallet_phone')->nullable();
            
            // Cryptocurrency specific fields
            $table->string('crypto_type')->nullable(); // bitcoin, ethereum, litecoin
            $table->string('crypto_address')->nullable();
            $table->string('crypto_network')->nullable(); // mainnet, testnet
            
            // Billing Address
            $table->string('billing_name')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('billing_phone')->nullable();
            $table->text('billing_address_line1')->nullable();
            $table->text('billing_address_line2')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_state')->nullable();
            $table->string('billing_postal_code')->nullable();
            $table->string('billing_country', 2)->nullable();
            
            // Security and Compliance
            $table->json('security_checks')->nullable(); // Results of security validations
            $table->json('compliance_data')->nullable(); // PCI compliance related data
            $table->boolean('requires_3d_secure')->default(false);
            $table->json('risk_assessment')->nullable(); // Risk scoring information
            
            // Usage and Statistics
            $table->integer('successful_payments_count')->default(0);
            $table->integer('failed_payments_count')->default(0);
            $table->decimal('total_payment_amount', 15, 2)->default(0.00);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('last_failed_at')->nullable();
            $table->text('last_failure_reason')->nullable();
            
            // Metadata and Configuration
            $table->json('metadata')->nullable(); // Additional provider-specific data
            $table->json('preferences')->nullable(); // User preferences for this payment method
            $table->json('restrictions')->nullable(); // Usage restrictions
            $table->decimal('daily_limit', 10, 2)->nullable();
            $table->decimal('monthly_limit', 12, 2)->nullable();
            $table->json('allowed_currencies')->nullable();
            $table->json('blocked_countries')->nullable();
            
            // Lifecycle Management
            $table->timestamp('expires_at')->nullable(); // For cards and temporary methods
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamp('deleted_at')->nullable(); // Soft delete
            $table->text('deactivation_reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('company_id');
            $table->index('client_id');
            $table->index('type');
            $table->index('provider');
            $table->index('provider_payment_method_id');
            $table->index('provider_customer_id');
            $table->index('token');
            $table->index('fingerprint');
            $table->index('is_default');
            $table->index('is_active');
            $table->index('verified');
            $table->index('card_brand');
            $table->index('card_last_four');
            $table->index('expires_at');
            $table->index('last_used_at');
            $table->index('deleted_at');
            $table->index(['company_id', 'client_id']);
            $table->index(['client_id', 'is_default']);
            $table->index(['client_id', 'is_active']);
            $table->index(['client_id', 'type']);
            $table->index(['provider', 'provider_payment_method_id']);
            $table->index(['type', 'is_active']);
            $table->index('created_by');
            $table->index('updated_by');

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Unique constraints
            $table->unique(['provider', 'provider_payment_method_id'], 'unique_provider_method');
            $table->unique(['client_id', 'fingerprint'], 'unique_client_fingerprint');
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