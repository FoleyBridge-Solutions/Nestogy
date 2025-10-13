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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('prefix')->nullable();
            $table->integer('number');
            $table->string('source')->nullable(); // Email, Phone, Portal, etc.
            $table->string('category')->nullable();
            $table->string('subject');
            $table->longText('details');
            $table->string('priority')->nullable(); // Low, Normal, High, Critical
            $table->string('status'); // Open, In Progress, Resolved, Closed
            $table->boolean('billable')->default(false);
            $table->timestamp('scheduled_at')->nullable();
            $table->boolean('onsite')->default(false);
            $table->string('vendor_ticket_number')->nullable();
            $table->string('feedback')->nullable();
            $table->timestamps();
            $table->decimal('sentiment_score', 3, 2)->nullable()->comment('Sentiment score from -1.00 (negative) to 1.00 (positive)');
            $table->enum('sentiment_label', ['POSITIVE', 'WEAK_POSITIVE', 'NEUTRAL', 'WEAK_NEGATIVE', 'NEGATIVE'])->nullable()->comment('Sentiment classification label');
            $table->timestamp('sentiment_analyzed_at')->nullable()->comment('When sentiment analysis was performed');
            $table->decimal('sentiment_confidence', 3, 2)->nullable()->comment('Confidence score for sentiment analysis (0.00 to 1.00)');
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->text('resolution_summary')->nullable();
            $table->boolean('client_can_reopen')->default(true);
            $table->timestamp('reopened_at')->nullable();
            $table->unsignedBigInteger('reopened_by')->nullable();
            $table->integer('resolution_count')->default(0);
            $table->string('type')->nullable();
            $table->timestamp('estimated_resolution_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('asset_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();

            // Indexes
            $table->index('number');
            $table->index('status');
            $table->index('priority');
            $table->index('client_id');
            $table->index('company_id');
            $table->index('assigned_to');
            $table->index('created_by');
            $table->index(['client_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index(['company_id', 'client_id']);
            $table->index('billable');
            $table->index('scheduled_at');
            $table->index('closed_at');
            $table->index('archived_at');
            $table->unique(['prefix', 'number']);
            $table->index(['sentiment_label', 'created_at'], 'idx_tickets_sentiment_created');
            $table->index(['sentiment_score', 'company_id'], 'idx_tickets_sentiment_company');
            $table->index('is_resolved');
            $table->index(['is_resolved', 'status']);
            $table->index('resolved_at');
            $table->index(['company_id', 'status'], 'idx_tickets_company_status');
            $table->index(['company_id', 'assigned_to', 'status'], 'idx_tickets_company_assigned_status');
            $table->index(['company_id', 'priority', 'status'], 'idx_tickets_company_priority_status');
            $table->index(['company_id', 'created_at'], 'idx_tickets_company_created');
            $table->index(['assigned_to', 'status'], 'idx_tickets_assigned_status');
            $table->index(['client_id', 'status'], 'idx_tickets_client_status');
            $table->index(['is_resolved', 'resolved_at'], 'idx_tickets_resolved');
            $table->index(['created_at', 'status'], 'idx_tickets_created_status');

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('closed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('set null');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('set null');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('set null');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
