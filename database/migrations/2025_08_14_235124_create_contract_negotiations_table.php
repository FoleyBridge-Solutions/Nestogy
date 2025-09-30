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
        Schema::create('contract_negotiations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('quote_id')->nullable();

            // Negotiation identification
            $table->string('negotiation_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();

            // Negotiation status and workflow
            $table->string('status')->default('active'); // active, paused, completed, cancelled
            $table->string('phase')->default('preparation'); // preparation, proposal, negotiation, approval, finalization
            $table->integer('round')->default(1); // Negotiation round

            // Timeline and deadlines
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('deadline')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_activity_at')->useCurrent();

            // Participants and permissions
            $table->json('internal_participants'); // Users from the company side
            $table->json('client_participants')->nullable(); // Client contacts involved
            $table->json('permissions')->nullable(); // Who can edit what

            // Negotiation context
            $table->json('objectives')->nullable(); // Business objectives and priorities
            $table->json('constraints')->nullable(); // Budget, timeline, technical constraints
            $table->json('competitive_context')->nullable(); // Competitive situation

            // Current state
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->json('pricing_history')->nullable(); // Track pricing changes
            $table->decimal('target_value', 12, 2)->nullable();
            $table->decimal('minimum_value', 12, 2)->nullable();

            // Success metrics
            $table->decimal('final_value', 12, 2)->nullable();
            $table->integer('duration_days')->nullable();
            $table->boolean('won')->nullable();
            $table->text('outcome_notes')->nullable();

            // Audit fields
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('assigned_to')->nullable(); // Lead negotiator
            $table->timestamps();

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('contract_id')->references('id')->on('contracts')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('set null');
            $table->foreign('current_version_id')->references('id')->on('contract_versions')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['client_id', 'status']);
            $table->index(['status', 'phase']);
            $table->index(['created_by', 'assigned_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_negotiations');
    }
};
