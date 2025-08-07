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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('opening_balance', 15, 2)->default(0.00);
            $table->string('currency_code', 3);
            $table->text('notes')->nullable();
            $table->integer('type')->nullable(); // Account type reference
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            $table->string('plaid_id')->nullable(); // For bank integration

            // Indexes
            $table->index('name');
            $table->index('currency_code');
            $table->index('type');
            $table->index('archived_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};