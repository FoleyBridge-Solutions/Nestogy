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
        Schema::create('plaid_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('plaid_item_id')->unique()->index();
            $table->text('plaid_access_token'); // Will be encrypted
            $table->string('institution_id')->nullable();
            $table->string('institution_name')->nullable();
            $table->enum('status', ['active', 'inactive', 'error', 'reauth_required'])->default('active');
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('consent_expiration_time')->nullable();
            $table->json('products')->nullable(); // ['transactions', 'auth', 'balance']
            $table->json('available_products')->nullable();
            $table->json('billed_products')->nullable();
            $table->string('webhook_url')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plaid_items');
    }
};
