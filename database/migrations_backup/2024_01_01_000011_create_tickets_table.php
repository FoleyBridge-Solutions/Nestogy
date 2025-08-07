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
            $table->string('prefix')->nullable();
            $table->integer('number');
            $table->string('source')->nullable(); // Email, Phone, Portal, etc.
            $table->string('category')->nullable();
            $table->string('subject');
            $table->longText('details');
            $table->string('priority')->nullable(); // Low, Normal, High, Critical
            $table->string('status'); // Open, In Progress, Resolved, Closed
            $table->boolean('billable')->default(false);
            $table->timestamp('schedule')->nullable();
            $table->boolean('onsite')->default(false);
            $table->string('vendor_ticket_number')->nullable();
            $table->string('feedback')->nullable();
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('closed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('vendor_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('contact_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('asset_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedBigInteger('invoice_id')->nullable()->index();
            $table->unsignedBigInteger('project_id')->nullable()->index();

            // Indexes
            $table->index('number');
            $table->index('status');
            $table->index('priority');
            $table->index('client_id');
            $table->index('assigned_to');
            $table->index('created_by');
            $table->index(['client_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index('billable');
            $table->index('schedule');
            $table->index('closed_at');
            $table->index('archived_at');
            $table->unique(['prefix', 'number']);
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