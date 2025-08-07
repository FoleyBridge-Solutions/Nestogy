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
            $table->date('date');
            $table->decimal('amount', 15, 2);
            $table->string('currency_code', 3);
            $table->string('method')->nullable(); // Cash, Check, Credit Card, Bank Transfer, etc.
            $table->string('reference')->nullable(); // Check number, transaction ID, etc.
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('plaid_transaction_id')->nullable(); // For bank integration

            // Indexes
            $table->index('date');
            $table->index('invoice_id');
            $table->index('account_id');
            $table->index('method');
            $table->index(['invoice_id', 'date']);
            $table->index('archived_at');
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