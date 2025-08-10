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
        Schema::create('client_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('quote_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('quote_type', ['service', 'product', 'project', 'maintenance', 'consulting', 'mixed'])->default('service');
            $table->date('quote_date');
            $table->date('expiry_date');
            $table->integer('valid_days')->default(30);
            $table->enum('status', ['draft', 'sent', 'viewed', 'accepted', 'rejected', 'expired', 'converted', 'cancelled'])->default('draft');
            $table->json('line_items'); // Array of line items with description, quantity, rate, etc.
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->integer('payment_terms')->default(30); // Net days
            $table->text('payment_instructions')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->enum('conversion_probability', ['low', 'medium', 'high', 'very_high'])->default('medium');
            $table->decimal('probability_percentage', 5, 2)->default(50);
            $table->text('probability_notes')->nullable();
            $table->json('follow_up_schedule')->nullable(); // Scheduled follow-ups
            $table->timestamp('last_follow_up')->nullable();
            $table->timestamp('next_follow_up')->nullable();
            $table->integer('follow_up_count')->default(0);
            $table->boolean('requires_approval')->default(false);
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->integer('view_count')->default(0);
            $table->json('view_history')->nullable(); // Track when and who viewed
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('client_signature_required')->default(false);
            $table->text('client_signature')->nullable(); // Base64 encoded signature
            $table->string('client_signature_name')->nullable();
            $table->timestamp('client_signed_at')->nullable();
            $table->string('client_ip_address')->nullable();
            $table->boolean('company_signature_required')->default(false);
            $table->text('company_signature')->nullable();
            $table->string('company_signature_name')->nullable();
            $table->timestamp('company_signed_at')->nullable();
            $table->foreignId('signed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('email_recipients')->nullable(); // Additional email recipients
            $table->string('email_template')->nullable();
            $table->json('email_history')->nullable(); // Track email sends
            $table->integer('email_count')->default(0);
            $table->timestamp('last_emailed')->nullable();
            $table->boolean('auto_follow_up')->default(true);
            $table->json('reminder_settings')->nullable();
            $table->json('conversion_data')->nullable(); // Data for conversion to invoice
            $table->string('converted_invoice_id')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('converted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('converted_amount', 15, 2)->nullable();
            $table->decimal('conversion_rate_percentage', 5, 2)->nullable();
            $table->text('conversion_notes')->nullable();
            $table->json('project_timeline')->nullable(); // Estimated project phases
            $table->date('project_start_date')->nullable();
            $table->date('project_end_date')->nullable();
            $table->integer('project_duration_days')->nullable();
            $table->json('resource_requirements')->nullable(); // Required resources/staff
            $table->json('deliverables')->nullable(); // Expected deliverables
            $table->json('assumptions')->nullable(); // Project assumptions
            $table->json('exclusions')->nullable(); // What's not included
            $table->json('risks')->nullable(); // Identified risks
            $table->json('competitors')->nullable(); // Competing quotes
            $table->string('win_probability_factors')->nullable();
            $table->decimal('competitor_pricing', 15, 2)->nullable();
            $table->text('competitive_advantage')->nullable();
            $table->json('upsell_opportunities')->nullable();
            $table->json('cross_sell_items')->nullable();
            $table->decimal('potential_additional_revenue', 15, 2)->default(0);
            $table->json('client_requirements')->nullable();
            $table->json('technical_specifications')->nullable();
            $table->json('attachments')->nullable(); // File attachments
            $table->json('related_documents')->nullable();
            $table->string('pdf_template')->nullable();
            $table->json('integration_settings')->nullable();
            $table->string('external_id')->nullable();
            $table->timestamp('last_sync')->nullable();
            $table->json('sync_errors')->nullable();
            $table->json('analytics_data')->nullable(); // Performance metrics
            $table->json('alerts')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->json('team_members')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('accessed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'client_id']);
            $table->index(['status', 'company_id']);
            $table->index('quote_number');
            $table->index('quote_type');
            $table->index('expiry_date');
            $table->index('conversion_probability');
            $table->index('requires_approval');
            $table->index('approval_status');
            $table->index(['status', 'expiry_date']);
            $table->index('assigned_to');
            $table->index('converted_invoice_id');
            $table->index('external_id');
            $table->index('next_follow_up');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_quotes');
    }
};