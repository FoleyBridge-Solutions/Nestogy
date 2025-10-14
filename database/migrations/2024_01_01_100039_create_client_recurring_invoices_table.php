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
        Schema::create('client_recurring_invoices', function (Blueprint $table) {
            $table->id();
                        $table->foreignId('company_id')->constrained()->onDelete('cascade');
                        $table->foreignId('client_id')->constrained()->onDelete('cascade');
                        $table->string('invoice_number');
                        $table->decimal('amount', 10, 2);
                        $table->enum('frequency', ['monthly', 'quarterly', 'semi-annually', 'annually'])->default('monthly');
                        $table->date('start_date');
                        $table->date('end_date')->nullable();
                        $table->date('next_invoice_date');
                        $table->enum('status', ['active', 'paused', 'cancelled'])->default('active');
                        $table->json('line_items');
                        $table->text('notes')->nullable();
                        $table->timestamps();

                        $table->index(['company_id', 'client_id']);
                        $table->index(['company_id', 'next_invoice_date']);
                        $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_recurring_invoices');
    }
};
