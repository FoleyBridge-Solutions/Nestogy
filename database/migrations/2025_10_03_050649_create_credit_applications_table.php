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
        Schema::create('credit_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('credit_note_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('applied_by')->nullable();
            $table->string('reversed_by')->nullable();
            $table->string('application_number')->nullable();
            $table->string('reference')->nullable();
            $table->string('application_type')->nullable();
            $table->string('application_method')->nullable();
            $table->string('status')->default('active');
            $table->decimal('applied_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2)->default(0);
            $table->string('currency_code')->nullable();
            $table->string('exchange_rate')->nullable();
            $table->decimal('tax_applied_amount', 15, 2)->default(0);
            $table->string('tax_application_breakdown')->nullable();
            $table->string('applies_to_tax')->nullable();
            $table->string('invoice_balance_before')->nullable();
            $table->string('invoice_balance_after')->nullable();
            $table->string('invoice_fully_credited')->nullable();
            $table->string('invoice_credit_percentage')->nullable();
            $table->string('line_item_applications')->nullable();
            $table->string('applies_to_specific_items')->nullable();
            $table->timestamp('application_date')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamp('effective_date')->nullable();
            $table->boolean('is_reversed')->default(false);
            $table->string('reversal_reason')->nullable();
            $table->decimal('reversal_amount', 15, 2)->default(0);
            $table->string('reversal_details')->nullable();
            $table->string('application_rules')->nullable();
            $table->string('auto_apply_to_future')->nullable();
            $table->string('priority')->nullable();
            $table->string('debit_gl_account')->nullable();
            $table->string('credit_gl_account')->nullable();
            $table->string('gl_entries')->nullable();
            $table->string('gl_posted')->nullable();
            $table->timestamp('gl_posted_at')->nullable();
            $table->string('affects_revenue_recognition')->nullable();
            $table->string('revenue_impact')->nullable();
            $table->string('revenue_adjustment')->nullable();
            $table->boolean('is_prorated')->default(false);
            $table->string('proration_details')->nullable();
            $table->string('service_period_start')->nullable();
            $table->string('service_period_end')->nullable();
            $table->string('customer_notified')->nullable();
            $table->timestamp('notification_sent_at')->nullable();
            $table->string('notification_details')->nullable();
            $table->string('requires_approval')->nullable();
            $table->string('approved')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->unsignedBigInteger('external_id')->nullable();
            $table->string('external_references')->nullable();
            $table->string('source_system')->nullable();
            $table->string('retry_count')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->string('error_log')->nullable();
            $table->string('failure_reason')->nullable();
            $table->text('application_notes')->nullable();
            $table->text('internal_notes')->nullable();
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
        Schema::dropIfExists('credit_applications');
    }
};
