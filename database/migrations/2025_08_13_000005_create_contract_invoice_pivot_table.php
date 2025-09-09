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
        Schema::create('contract_invoice', function (Blueprint $table) {
            $table->id();
            
            // Multi-tenancy
            $table->unsignedBigInteger('company_id')->index();
            
            // Relationships
            $table->unsignedBigInteger('contract_id')->index();
            $table->unsignedBigInteger('invoice_id')->index();
            
            // Invoice relationship details
            $table->string('invoice_type')->nullable(); // 'initial', 'recurring', 'milestone', 'final'
            $table->decimal('invoiced_amount', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('milestone_id')->nullable(); // If invoice is for a milestone
            
            // Billing period for recurring contracts
            $table->date('billing_period_start')->nullable();
            $table->date('billing_period_end')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
            
            $table->foreign('contract_id')
                ->references('id')
                ->on('contracts')
                ->onDelete('cascade');
            
            $table->foreign('invoice_id')
                ->references('id')
                ->on('invoices')
                ->onDelete('cascade');
            
            $table->foreign('milestone_id')
                ->references('id')
                ->on('contract_milestones')
                ->onDelete('set null');
            
            // Composite indexes
            $table->index(['company_id', 'contract_id']);
            $table->index(['company_id', 'invoice_id']);
            
            // Prevent duplicate entries
            $table->unique(['contract_id', 'invoice_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_invoice');
    }
};