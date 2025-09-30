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
        Schema::create('contract_billing_model_definitions', function (Blueprint $table) {
            $table->id();

            // Multi-tenancy
            $table->unsignedBigInteger('company_id')->index();

            // Billing model definition
            $table->string('slug')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('calculator_class'); // PHP class that implements the billing logic
            $table->json('config')->nullable(); // Model-specific configuration
            $table->json('default_rates')->nullable(); // Default rates and pricing
            $table->json('field_requirements')->nullable(); // Fields required for this model
            $table->json('validation_rules')->nullable(); // Validation rules
            $table->boolean('supports_assets')->default(false);
            $table->boolean('supports_contacts')->default(false);
            $table->boolean('supports_usage')->default(false);
            $table->boolean('supports_tiers')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            // Indexes
            $table->index(['company_id', 'slug']);
            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'sort_order']);

            // Unique constraint
            $table->unique(['company_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_billing_model_definitions');
    }
};
