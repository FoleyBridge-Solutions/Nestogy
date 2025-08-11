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
        Schema::create('credit_note_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('credit_note_id');
            $table->unsignedBigInteger('invoice_item_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('tax_category_id')->nullable();
            
            // Item identification and description
            $table->string('item_code', 100)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('item_type', [
                'product', 'service', 'voip_service', 'equipment', 
                'installation', 'maintenance', 'regulatory_fee', 
                'tax_adjustment', 'discount', 'other'
            ])->default('service');
            
            // Quantity and pricing
            $table->decimal('quantity', 12, 4)->default(1.0000);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_total', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('discount_percentage', 8, 4)->default(0.0000);
            
            // Tax information
            $table->decimal('tax_rate', 8, 4)->default(0.0000);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->json('tax_breakdown')->nullable(); // Detailed tax calculations per jurisdiction
            $table->boolean('tax_inclusive')->default(false);
            $table->boolean('tax_exempt')->default(false);
            $table->string('tax_exemption_code', 50)->nullable();
            
            // VoIP-specific fields
            $table->enum('voip_service_type', [
                'local_service', 'long_distance', 'international', 
                'toll_free', 'directory_assistance', 'equipment_rental',
                'installation_fee', 'activation_fee', 'regulatory_fee',
                'e911_fee', 'usf_fee', 'number_porting', 'other'
            ])->nullable();
            $table->json('usage_details')->nullable(); // Minutes, calls, data usage etc.
            $table->decimal('regulatory_fees', 10, 4)->default(0.0000);
            $table->json('jurisdiction_breakdown')->nullable(); // State/local jurisdiction details
            
            // Proration and adjustment details
            $table->boolean('is_prorated')->default(false);
            $table->json('proration_details')->nullable();
            $table->date('service_period_start')->nullable();
            $table->date('service_period_end')->nullable();
            $table->integer('proration_days')->nullable();
            $table->integer('total_period_days')->nullable();
            
            // Original invoice item reference
            $table->decimal('original_quantity', 12, 4)->nullable();
            $table->decimal('original_unit_price', 15, 2)->nullable();
            $table->decimal('original_line_total', 15, 2)->nullable();
            $table->decimal('original_tax_amount', 15, 2)->nullable();
            
            // Refund and credit tracking
            $table->decimal('credited_quantity', 12, 4)->default(0.0000);
            $table->decimal('credited_amount', 15, 2)->default(0.00);
            $table->decimal('remaining_credit', 15, 2)->default(0.00);
            $table->boolean('fully_credited')->default(false);
            
            // Equipment return details (for equipment items)
            $table->json('equipment_details')->nullable();
            $table->enum('equipment_condition', [
                'new', 'excellent', 'good', 'fair', 'poor', 'damaged'
            ])->nullable();
            $table->decimal('condition_adjustment', 8, 4)->default(0.0000);
            $table->string('serial_number')->nullable();
            $table->boolean('equipment_returned')->default(false);
            $table->date('return_date')->nullable();
            
            // GL and accounting integration
            $table->string('gl_account_code', 50)->nullable();
            $table->string('revenue_account_code', 50)->nullable();
            $table->string('tax_account_code', 50)->nullable();
            $table->json('accounting_entries')->nullable();
            
            // Order and display
            $table->integer('sort_order')->default(0);
            
            // Metadata and audit
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['credit_note_id', 'sort_order']);
            $table->index(['company_id', 'credit_note_id']);
            $table->index(['invoice_item_id']);
            $table->index(['product_id']);
            $table->index(['item_type', 'voip_service_type']);
            $table->index(['is_prorated', 'service_period_start']);
            $table->index(['equipment_returned', 'return_date']);
            $table->index(['fully_credited']);
            $table->index(['gl_account_code']);
            
            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('credit_note_id')->references('id')->on('credit_notes')->onDelete('cascade');
            $table->foreign('invoice_item_id')->references('id')->on('invoice_items')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_note_items');
    }
};