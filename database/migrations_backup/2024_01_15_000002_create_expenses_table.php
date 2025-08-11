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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('submitted_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->date('expense_date');
            $table->string('vendor')->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('payment_method', 50);
            $table->string('reference_number')->nullable();
            
            $table->enum('status', [
                'draft',
                'submitted', 
                'pending_approval',
                'approved',
                'rejected',
                'paid',
                'invoiced',
                'cancelled'
            ])->default('draft');
            
            $table->text('approval_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('tags')->nullable();
            
            $table->boolean('is_billable')->default(false);
            $table->decimal('markup_percentage', 5, 2)->nullable();
            $table->decimal('markup_amount', 10, 2)->nullable();
            $table->decimal('total_billable_amount', 10, 2)->nullable();
            $table->timestamp('invoiced_at')->nullable();
            
            // Mileage tracking
            $table->decimal('mileage', 8, 2)->nullable();
            $table->decimal('mileage_rate', 5, 2)->nullable();
            
            // Additional fields
            $table->string('location')->nullable();
            $table->text('business_purpose')->nullable();
            $table->json('attendees')->nullable();
            
            // Recurring expense fields
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurring_frequency', [
                'weekly',
                'biweekly', 
                'monthly',
                'quarterly',
                'annually'
            ])->nullable();
            $table->date('recurring_until')->nullable();
            $table->unsignedBigInteger('parent_expense_id')->nullable();
            
            // Additional metadata
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['submitted_by', 'status']);
            $table->index(['expense_date', 'company_id']);
            $table->index(['is_billable', 'invoiced_at']);
            $table->index(['category_id']);
            $table->index(['client_id']);
            $table->index(['project_id']);
            $table->index(['parent_expense_id']);

            // Foreign key constraints can be added here if needed
            // $table->foreign('company_id')->references('id')->on('companies');
            // $table->foreign('submitted_by')->references('id')->on('users');
            // $table->foreign('category_id')->references('id')->on('expense_categories');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};