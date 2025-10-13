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
        Schema::create('contract_milestones', function (Blueprint $table) {
            $table->id();

            // Multi-tenancy
            $table->unsignedBigInteger('company_id')->index();

            // Contract relationship
            $table->unsignedBigInteger('contract_id')->index();

            // Milestone details
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('due_date');
            $table->date('completed_date')->nullable();

            // Status tracking
            $table->string('status')->default('pending')->index();
            // Statuses: pending, in_progress, completed, overdue, cancelled

            // Financial information
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->boolean('is_billable')->default(true);
            $table->boolean('is_invoiced')->default(false);
            $table->unsignedBigInteger('invoice_id')->nullable()->index();

            // Deliverables and acceptance
            $table->json('deliverables')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->text('approval_notes')->nullable();

            // Progress tracking
            $table->unsignedInteger('progress_percentage')->default(0);
            $table->text('progress_notes')->nullable();

            // Dependencies
            $table->unsignedBigInteger('depends_on_milestone_id')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            // Notifications
            $table->boolean('send_reminder')->default(true);
            $table->unsignedInteger('reminder_days_before')->default(7);
            $table->timestamp('reminder_sent_at')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

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
                ->onDelete('set null');

            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('depends_on_milestone_id')
                ->references('id')
                ->on('contract_milestones')
                ->onDelete('set null');

            // Composite indexes
            $table->index(['company_id', 'contract_id']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'due_date']);
            $table->index(['contract_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_milestones');
    }
};
