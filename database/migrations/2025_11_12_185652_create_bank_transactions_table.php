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
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('plaid_item_id')->nullable()->constrained('plaid_items')->nullOnDelete();
            $table->string('plaid_transaction_id')->unique()->index();
            $table->string('plaid_account_id')->index();
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->date('authorized_date')->nullable();
            $table->string('name'); // Transaction name from Plaid
            $table->string('merchant_name')->nullable();
            $table->json('category')->nullable(); // Plaid categories
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->boolean('pending')->default(false);
            $table->string('payment_channel')->nullable(); // online, in store, other
            $table->string('transaction_type')->nullable(); // place, special, unresolved
            $table->json('location')->nullable(); // address, city, state, etc.
            $table->json('payment_meta')->nullable(); // reference number, etc.
            $table->boolean('is_reconciled')->default(false);
            $table->foreignId('reconciled_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('reconciled_expense_id')->nullable()->constrained('expenses')->nullOnDelete();
            $table->timestamp('reconciled_at')->nullable();
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reconciliation_notes')->nullable();
            $table->boolean('is_ignored')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['company_id', 'account_id']);
            $table->index(['company_id', 'is_reconciled']);
            $table->index(['company_id', 'date']);
            $table->index(['pending']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
