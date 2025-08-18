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
        Schema::create('tax_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            
            // Reference to the document/invoice this calculation belongs to
            $table->morphs('calculable'); // calculable_type, calculable_id
            
            // Calculation metadata
            $table->string('calculation_id')->unique(); // Unique ID for this calculation
            $table->string('engine_type'); // voip, general, digital, equipment
            $table->string('category_type')->nullable(); // Service category
            $table->enum('calculation_type', ['quote', 'invoice', 'preview', 'adjustment'])->default('preview');
            
            // Input parameters (for audit and recalculation)
            $table->decimal('base_amount', 15, 2);
            $table->integer('quantity')->default(1);
            $table->json('input_parameters'); // All calculation inputs
            
            // Customer information
            $table->json('customer_data')->nullable(); // Address, VAT number, etc.
            $table->json('service_address')->nullable(); // Service delivery address
            
            // Tax calculation results
            $table->decimal('total_tax_amount', 15, 2)->default(0);
            $table->decimal('final_amount', 15, 2); // base + tax
            $table->decimal('effective_tax_rate', 8, 6)->default(0); // As percentage
            $table->json('tax_breakdown'); // Detailed breakdown by jurisdiction/type
            
            // API integration details
            $table->json('api_enhancements')->nullable(); // Results from API integrations
            $table->json('jurisdictions')->nullable(); // Tax jurisdictions applied
            $table->json('exemptions_applied')->nullable(); // Any tax exemptions
            
            // Calculation engine information
            $table->json('engine_metadata'); // Engine versions, API versions, etc.
            $table->json('api_calls_made')->nullable(); // Track which APIs were called
            
            // Validation and compliance
            $table->boolean('validated')->default(false);
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users');
            $table->text('validation_notes')->nullable();
            
            // Status tracking
            $table->enum('status', ['draft', 'calculated', 'applied', 'adjusted', 'voided'])->default('calculated');
            $table->json('status_history')->nullable(); // Track status changes
            
            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->json('change_log')->nullable(); // Track all changes
            
            // Performance tracking
            $table->integer('calculation_time_ms')->nullable(); // Time taken to calculate
            $table->integer('api_calls_count')->default(0);
            $table->decimal('api_cost', 10, 4)->default(0); // Cost of API calls
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['company_id', 'calculation_type', 'status']);
            $table->index(['engine_type', 'category_type']);
            $table->index(['created_at', 'company_id']);
            $table->index('calculation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_calculations');
    }
};