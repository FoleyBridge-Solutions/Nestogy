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
        Schema::create('tax_exemption_usage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('tax_exemption_id');
            
            // Reference to what the exemption was applied to
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('quote_id')->nullable();
            $table->unsignedBigInteger('invoice_item_id')->nullable();
            
            // Tax calculation details
            $table->decimal('original_tax_amount', 12, 4); // Tax amount before exemption
            $table->decimal('exempted_amount', 12, 4); // Amount exempted
            $table->decimal('final_tax_amount', 12, 4); // Final tax after exemption
            
            // Usage details
            $table->string('exemption_reason')->nullable();
            $table->json('calculation_details')->nullable();
            $table->datetime('used_at');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'tax_exemption_id']);
            $table->index(['tax_exemption_id', 'used_at']);
            $table->index(['client_id', 'used_at']);
            $table->index('invoice_id');
            $table->index('quote_id');
            $table->index('invoice_item_id');
            $table->index('used_at');
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('tax_exemption_id')->references('id')->on('tax_exemptions')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
            $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('set null');
            $table->foreign('invoice_item_id')->references('id')->on('invoice_items')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_exemption_usage');
    }
};
