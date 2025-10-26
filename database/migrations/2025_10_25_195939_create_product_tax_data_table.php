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
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('tax_profile_id')->nullable()->constrained('tax_profiles')->onDelete('set null');
            $table->json('tax_data');
            $table->json('calculated_taxes')->nullable();
            $table->unsignedBigInteger('jurisdiction_id')->nullable();
            $table->decimal('effective_tax_rate', 8, 6)->nullable();
            $table->decimal('total_tax_amount', 15, 2)->nullable();
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();
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
