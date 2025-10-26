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
        Schema::create('service_tax_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('tax_jurisdiction_id');
            $table->unsignedBigInteger('tax_category_id');
            $table->string('service_type', 50);
            $table->string('tax_type');
            $table->string('tax_name');
            $table->string('authority_name');
            $table->string('tax_code', 50)->nullable();
            $table->text('description')->nullable();
            $table->string('regulatory_code', 50)->nullable();
            $table->string('rate_type');
            $table->decimal('percentage_rate', 8, 6)->nullable();
            $table->decimal('fixed_amount', 10, 2)->nullable();
            $table->decimal('minimum_threshold', 10, 2)->nullable();
            $table->decimal('maximum_amount', 10, 2)->nullable();
            $table->string('calculation_method');
            $table->json('service_types')->nullable();
            $table->json('conditions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_recoverable')->default(false);
            $table->boolean('is_compound')->default(false);
            $table->integer('priority')->default(0);
            $table->date('effective_date');
            $table->date('expiry_date')->nullable();
            $table->string('external_id')->nullable();
            $table->string('source')->nullable();
            $table->timestamp('last_updated_from_source')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_tax_rates');
    }
};
