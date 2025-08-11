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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            
            // Payment details
            $table->string('payment_method', 50);
            $table->string('payment_reference')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            
            // Gateway information
            $table->string('gateway', 50)->default('manual');
            $table->string('gateway_transaction_id')->nullable();
            $table->decimal('gateway_fee', 8, 2)->nullable();
            
            // Payment status and dates
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'cancelled',
                'refunded',
                'partial_refund',
                'chargeback'
            ])->default('pending');
            
            $table->timestamp('payment_date');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            // Refund and chargeback tracking
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->text('refund_reason')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->decimal('chargeback_amount', 10, 2)->nullable();
            $table->text('chargeback_reason')->nullable();
            $table->timestamp('chargeback_date')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['client_id', 'status']);
            $table->index(['invoice_id']);
            $table->index(['payment_date', 'company_id']);
            $table->index(['gateway', 'gateway_transaction_id']);
            $table->index(['payment_reference']);
            $table->index(['processed_by']);

            // Foreign key constraints can be added here if needed
            // $table->foreign('company_id')->references('id')->on('companies');
            // $table->foreign('client_id')->references('id')->on('clients');
            // $table->foreign('invoice_id')->references('id')->on('invoices');
            // $table->foreign('processed_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};