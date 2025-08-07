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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('currency_code', 3);
            $table->date('date');
            $table->string('reference')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('receipt')->nullable(); // File path to receipt
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('account_id')->nullable()->constrained()->onDelete('set null');
            $table->string('plaid_transaction_id')->nullable(); // For bank integration

            // Indexes
            $table->index('date');
            $table->index('vendor_id');
            $table->index('client_id');
            $table->index('category_id');
            $table->index('account_id');
            $table->index(['client_id', 'date']);
            $table->index(['category_id', 'date']);
            $table->index('archived_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};