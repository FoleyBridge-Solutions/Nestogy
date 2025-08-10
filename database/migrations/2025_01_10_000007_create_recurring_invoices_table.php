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
            
            // Company and relationships
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            
            // Basic information
            $table->string('title');
            $table->text('description')->nullable();
            
            // Billing configuration
            $table->enum('billing_frequency', [
                'weekly', 
                'bi_weekly', 
                'monthly', 
                'quarterly', 
                'semi_annually', 
                'annually', 
                'bi_annually'
            ]);
            $table->decimal('amount', 15, 2);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestamp('next_invoice_date');
            $table->timestamp('last_invoice_date')->nullable();
            $table->integer('invoice_due_days')->default(30);
            
            // Automation settings
            $table->boolean('auto_generate')->default(true);
            $table->boolean('auto_send')->default(false);
            $table->string('payment_terms', 50)->nullable();
            
            // Financial settings
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->integer('billing_cycle_day')->nullable(); // For monthly billing on specific day
            $table->boolean('proration_enabled')->default(true);
            
            // Escalation settings
            $table->decimal('escalation_percentage', 5, 2)->default(0);
            $table->enum('escalation_frequency', ['annual', 'biennial'])->default('annual');
            $table->timestamp('last_escalation_date')->nullable();
            
            // Status tracking
            $table->enum('status', [
                'active', 
                'paused', 
                'completed', 
                'cancelled', 
                'expired'
            ])->default('active');
            $table->timestamp('paused_at')->nullable();
            $table->string('pause_reason')->nullable();
            
            // Statistics
            $table->integer('invoices_generated')->default(0);
            $table->decimal('total_revenue_generated', 15, 2)->default(0);
            
            // Metadata and audit
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['company_id', 'status'], 'ri_company_status_idx');
            $table->index(['contract_id', 'status'], 'ri_contract_status_idx');
            $table->index(['client_id', 'status'], 'ri_client_status_idx');
            $table->index(['status', 'next_invoice_date'], 'ri_status_next_date_idx');
            $table->index(['status', 'auto_generate', 'next_invoice_date'], 'ri_status_auto_next_idx');
            $table->index(['billing_frequency', 'status'], 'ri_frequency_status_idx');
            $table->index(['next_invoice_date'], 'ri_next_date_idx');
            $table->index(['last_escalation_date', 'escalation_frequency'], 'ri_escalation_idx');
            
            // Composite indexes for common queries
            $table->index(['company_id', 'status', 'next_invoice_date'], 'ri_company_status_date_idx');
            $table->index(['contract_id', 'status', 'billing_frequency'], 'ri_contract_status_freq_idx');
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