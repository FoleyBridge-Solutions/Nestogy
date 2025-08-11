<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Recurring Usage Data Table
 * 
 * Stores usage data (CDR records, minutes, data usage) for VoIP recurring billing.
 * Supports various service types and usage metrics for accurate billing calculations.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recurring_usage_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('recurring_id');
            $table->unsignedBigInteger('client_id');
            
            // Usage identification
            $table->string('service_type', 50); // hosted_pbx, sip_trunking, long_distance, etc.
            $table->date('usage_date');
            $table->datetime('usage_timestamp')->nullable();
            
            // Usage metrics
            $table->decimal('usage_amount', 15, 4); // Minutes, MB, calls, etc.
            $table->string('usage_unit', 20)->default('minutes'); // minutes, mb, gb, calls, lines
            $table->decimal('rate', 8, 4)->default(0); // Rate per unit
            $table->decimal('cost', 10, 2)->default(0); // Calculated cost
            
            // Call/Session details (for VoIP)
            $table->string('from_number', 50)->nullable();
            $table->string('to_number', 50)->nullable();
            $table->string('call_type', 20)->nullable(); // local, long_distance, international
            $table->string('direction', 10)->nullable(); // inbound, outbound
            $table->integer('duration_seconds')->nullable();
            
            // Location/routing information
            $table->string('origin_location', 100)->nullable();
            $table->string('destination_location', 100)->nullable();
            $table->string('carrier', 100)->nullable();
            $table->string('route', 100)->nullable();
            
            // Billing and processing
            $table->enum('billing_status', ['pending', 'processed', 'invoiced', 'disputed'])
                  ->default('pending');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->date('billing_period_start')->nullable();
            $table->date('billing_period_end')->nullable();
            
            // External system references
            $table->string('external_id', 100)->nullable(); // CDR ID from phone system
            $table->string('source_system', 50)->nullable(); // Which system provided the data
            $table->datetime('imported_at')->nullable();
            
            // Metadata for additional fields
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('recurring_id')->references('id')->on('recurring')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['recurring_id', 'usage_date'], 'usage_recurring_date_idx');
            $table->index(['client_id', 'service_type', 'usage_date'], 'usage_client_service_date_idx');
            $table->index(['billing_status', 'usage_date'], 'usage_billing_status_date_idx');
            $table->index(['usage_date', 'service_type'], 'usage_date_service_idx');
            $table->index('external_id', 'usage_external_id_idx');
            $table->index(['billing_period_start', 'billing_period_end'], 'usage_billing_period_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_usage_data');
    }
};