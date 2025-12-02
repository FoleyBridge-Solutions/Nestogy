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
        Schema::create('billing_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            
            // What happened
            $table->string('action'); // invoice_generated, invoice_approved, invoice_voided, settings_changed, etc.
            $table->string('entity_type')->nullable(); // Ticket, Invoice, etc.
            $table->unsignedBigInteger('entity_id')->nullable();
            
            // Related entities
            $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            
            // Details
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Store calculation details, before/after values, etc.
            
            // Context
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'action']);
            $table->index(['company_id', 'user_id']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_audit_logs');
    }
};
