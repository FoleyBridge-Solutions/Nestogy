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
        Schema::create('product_tax_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('tax_profile_id')->nullable();
            $table->json('tax_data');
            $table->json('calculated_taxes')->nullable();
            $table->unsignedBigInteger('jurisdiction_id')->nullable();
            $table->decimal('effective_tax_rate', 5, 2)->nullable();
            $table->decimal('total_tax_amount', 10, 2)->nullable();
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('company_id');
            $table->index('product_id');
            $table->index('tax_profile_id');
            $table->unique(['company_id', 'product_id']);
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('tax_profile_id')->references('id')->on('tax_profiles')->onDelete('set null');
            // Comment out until tax_jurisdictions table exists
            // $table->foreign('jurisdiction_id')->references('id')->on('tax_jurisdictions')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_tax_data');
    }
};