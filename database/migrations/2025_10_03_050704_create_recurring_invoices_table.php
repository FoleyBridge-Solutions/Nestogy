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
        Schema::create('recurring_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('billing_frequency')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamp('next_invoice_date')->nullable();
            $table->timestamp('last_invoice_date')->nullable();
            $table->string('invoice_due_days')->nullable();
            $table->string('auto_generate')->nullable();
            $table->string('auto_send')->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('tax_rate')->nullable();
            $table->string('discount_percentage')->nullable();
            $table->string('billing_cycle_day')->nullable();
            $table->boolean('proration_enabled')->default(false);
            $table->string('escalation_percentage')->nullable();
            $table->string('escalation_frequency')->nullable();
            $table->timestamp('last_escalation_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('paused_at')->nullable();
            $table->string('pause_reason')->nullable();
            $table->string('invoices_generated')->nullable();
            $table->decimal('total_revenue_generated', 15, 2)->default(0);
            $table->string('metadata')->nullable();
            $table->string('created_by')->nullable();
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
        Schema::dropIfExists('recurring_invoices');
    }
};
