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
            $table->unsignedBigInteger('credit_note_id')->nullable();
            $table->unsignedBigInteger('invoice_item_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('tax_category_id')->nullable();
            $table->string('item_code')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('item_type')->nullable();
            $table->string('quantity')->nullable();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->string('discount_percentage')->nullable();
            $table->string('tax_rate')->nullable();
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->string('tax_breakdown')->nullable();
            $table->string('tax_inclusive')->nullable();
            $table->string('tax_exempt')->nullable();
            $table->string('tax_exemption_code')->nullable();
            $table->string('voip_service_type')->nullable();
            $table->string('usage_details')->nullable();
            $table->string('regulatory_fees')->nullable();
            $table->string('jurisdiction_breakdown')->nullable();
            $table->boolean('is_prorated')->default(false);
            $table->string('proration_details')->nullable();
            $table->string('service_period_start')->nullable();
            $table->string('service_period_end')->nullable();
            $table->string('proration_days')->nullable();
            $table->decimal('total_period_days', 15, 2)->default(0);
            $table->string('original_quantity')->nullable();
            $table->decimal('original_unit_price', 15, 2)->default(0);
            $table->decimal('original_line_total', 15, 2)->default(0);
            $table->decimal('original_tax_amount', 15, 2)->default(0);
            $table->string('credited_quantity')->nullable();
            $table->decimal('credited_amount', 15, 2)->default(0);
            $table->string('remaining_credit')->nullable();
            $table->string('fully_credited')->nullable();
            $table->string('equipment_details')->nullable();
            $table->string('equipment_condition')->nullable();
            $table->string('condition_adjustment')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('equipment_returned')->nullable();
            $table->timestamp('return_date')->nullable();
            $table->string('gl_account_code')->nullable();
            $table->string('revenue_account_code')->nullable();
            $table->string('tax_account_code')->nullable();
            $table->string('accounting_entries')->nullable();
            $table->string('sort_order')->nullable();
            $table->string('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes('archived_at');
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
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
